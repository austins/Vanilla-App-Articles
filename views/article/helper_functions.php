<?php defined('APPLICATION') or exit();

if (!function_exists('ShowArticleOptions')) {
    function ShowArticleOptions($Article) {
        // If $Article type is an array, then cast it to an object.
        if (is_array($Article))
            $Article = (object)$Article;

        $Sender = Gdn::Controller();
        $Session = Gdn::Session();
        $Options = array();

        // Can the user edit?
        if ($Session->CheckPermission('Articles.Articles.Edit'))
            $Options['EditArticle'] = array(
                'Label' => T('Edit'),
                'Url' => '/compose/editarticle/' . $Article->ArticleID);

        // Can the user close?
        if ($Session->CheckPermission('Articles.Articles.Close')) {
            $NewClosed = (int)!$Article->Closed;
            $Options['CloseArticle'] = array(
                'Label' => T($Article->Closed ? 'Reopen' : 'Close'),
                'Url' => "/article/close/{$Article->ArticleID}?close={$NewClosed}",
                'Class' => 'Hijack');
        }

        // Can the user delete?
        if ($Session->CheckPermission('Articles.Articles.Delete')) {
            $ArticleCategoryModel = new ArticleCategoryModel();
            $Category = $ArticleCategoryModel->GetByID(val('ArticleCategoryID', $Article));

            $Options['DeleteArticle'] = array(
                'Label' => T('Delete'),
                'Url' => '/article/delete/' . $Article->ArticleID,
                'Class' => 'DeleteArticle Popup');

            if (strtolower($Sender->ControllerName) === "articlecontroller")
                $Options['DeleteArticle']['Url'] .= '?&target=' . urlencode(ArticleCategoryUrl($Category));
        }

        // Render the article options menu.
        if (!empty($Options)) {
            echo '<div class="Options">';
            echo '<span class="ToggleFlyout OptionsMenu">';
            echo '<span class="OptionsTitle" title="' . T('Options') . '">' . T('Options') . '</span>';
            echo Sprite('SpFlyoutHandle', 'Arrow');
            echo '<ul class="Flyout MenuItems" style="display: none;">';
            foreach ($Options as $Code => $Option) {
                echo Wrap(Anchor($Option['Label'], $Option['Url'], val('Class', $Option, $Code)), 'li');
            }
            echo '</ul>';
            echo '</span>';
            echo '</div>';
        }
    }
}

if (!function_exists('ArticleTag')) {
    function ArticleTag($Article, $Column, $Code, $CssClass = false) {
        if (is_array($Article))
            $Article = (object)$Article;

        if ((is_numeric($Article->$Column) && !$Article->$Column)
            || (!is_numeric($Article->$Column) && strcasecmp($Article->$Column, $Code) != 0)
        )
            return '';

        if (!$CssClass)
            $CssClass = "Tag-$Code";

        return ' <span class="Tag ' . $CssClass . '" title="' . htmlspecialchars(T($Code)) . '">' . T($Code) . '</span> ';
    }
}

if (!function_exists('ShowCommentForm')) {
    function ShowCommentForm() {
        $Session = Gdn::Session();
        $Controller = Gdn::Controller();
        $Article = $Controller->Article;
        $UserCanClose = $Session->CheckPermission('Articles.Articles.Close');
        $UserCanComment = $Session->CheckPermission('Articles.Comments.Add');
        $canGuestsComment = C('Articles.Comments.AllowGuests', false);

        // Closed notification
        if ((bool)$Article->Closed) {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed"><?php echo T('This article has been closed.'); ?></div>
            </div>
        <?php
        } else if (!$Session->isValid() && !$canGuestsComment) {
                ?>
                <div class="Foot Closed">
                    <div class="Note Closed SignInOrRegister"><?php
                        $Popup = (C('Garden.SignIn.Popup')) ? ' class="Popup"' : '';
                        echo FormatString(
                            T('Sign In or Register to Comment.',
                                '<a href="{SignInUrl,html}"{Popup}>Sign In</a> or <a href="{RegisterUrl,html}">Register</a> to comment.'),
                            array(
                                'SignInUrl' => Url(SignInUrl(Url(''))),
                                'RegisterUrl' => Url(RegisterUrl(Url(''))),
                                'Popup' => $Popup
                            )
                        ); ?>
                    </div>
                </div>
            <?php
        }

        if ((($Article->Closed == '1') && $UserCanClose)
                || (($Article->Closed == '0') && $UserCanComment)
                || (!$Session->isValid() && $canGuestsComment))
            echo $Controller->FetchView('comment', 'compose', 'Articles');
    }
}

if (!function_exists('WriteArticleReactions')):
    function WriteArticleReactions($Comment, $Type = 'Comment') {
        list($RecordType, $RecordID) = RecordType($Comment);

        Gdn::Controller()->EventArguments['RecordType'] = strtolower($RecordType);
        Gdn::Controller()->EventArguments['RecordID'] = $RecordID;

        echo '<div class="Reactions">';
        Gdn_Theme::BulletRow();

        $Session = Gdn::Session();
        $GuestCommenting = (C('Articles.Comments.AllowGuests', false) && !$Session->IsValid());
        if (C('Articles.Comments.EnableThreadedComments', true) && !$Comment->ParentArticleCommentID) {
            if ($Session->IsValid() || $GuestCommenting) {
                echo Anchor('<span class="ReactSprite ReactReply"></span> Reply',
                    '/compose/comment/' . $Comment->ArticleID . '/' . $Comment->ArticleCommentID,
                    'ReactButton ReplyLink Visible');
            }
        }
        
        Gdn::Controller()->FireEvent('AfterArticleReactions');
        echo '</div>';
    }
endif;

if (!function_exists('GetCommentOptions')):
    function GetCommentOptions($Comment) {
        $Options = array();

        if (!is_numeric(val('ArticleCommentID', $Comment)))
            return $Options;

        $Sender = Gdn::Controller();
        $Session = Gdn::Session();

        $Article = & $Sender->Article;
        $ArticleCategoryID = val('ArticleCategoryID', $Article);

        // Determine if we still have time to edit
        $EditContentTimeout = C('Garden.EditContentTimeout', -1);
        $CanEdit = $EditContentTimeout == -1 || strtotime($Comment->DateInserted) + $EditContentTimeout > time();

        // Don't allow guests to edit.
        if (!$Session->IsValid())
            $CanEdit = false;

        $TimeLeft = '';

        if ($CanEdit && $EditContentTimeout > 0 && !$Session->CheckPermission('Articles.Articles.Edit')) {
            $TimeLeft = strtotime($Comment->DateInserted) + $EditContentTimeout - time();
            $TimeLeft = $TimeLeft > 0 ? ' (' . Gdn_Format::Seconds($TimeLeft) . ')' : '';
        }

        // Can the user edit the comment?
        if (($CanEdit && $Session->UserID == $Comment->InsertUserID) || $Session->CheckPermission('Articles.Comments.Edit'))
            $Options['EditComment'] = array('Label' => T('Edit') . ' ' . $TimeLeft,
                'Url' => '/articles/compose/editcomment/' . $Comment->ArticleCommentID, 'EditComment');

        // Can the user delete the comment?
        $SelfDeleting = ($CanEdit && $Session->UserID == $Comment->InsertUserID && C('Articles.Comments.AllowSelfDelete'));
        if ($SelfDeleting || $Session->CheckPermission('Articles.Comments.Delete'))
            $Options['DeleteComment'] = array('Label' => T('Delete'),
                'Url' => '/articles/article/deletecomment/' . $Comment->ArticleCommentID . '/' . $Session->TransientKey()
                    . '/?Target=' . urlencode('/article/' . Gdn_Format::Date($Article->DateInserted, '%Y') . '/'
                        . $Article->UrlCode), 'Class' => 'DeleteComment');

        // Allow plugins to add options
        $Sender->EventArguments['CommentOptions'] = & $Options;
        $Sender->EventArguments['Comment'] = $Comment;
        $Sender->FireEvent('CommentOptions');

        return $Options;
    }
endif;

if (!function_exists('WriteCommentOptions')):
    function WriteCommentOptions($Comment) {
        $Options = GetCommentOptions($Comment);

        if (count($Options) > 0) {
            echo '<div class="Options">';
            echo '<span class="ToggleFlyout OptionsMenu">';
            echo '<span class="OptionsTitle" title="' . T('Options') . '">' . T('Options') . '</span>';
            echo Sprite('SpFlyoutHandle', 'Arrow');
            echo '<ul class="Flyout MenuItems">';
            foreach ($Options as $Code => $Option) {
                echo Wrap(Anchor($Option['Label'], $Option['Url'], val('Class', $Option, $Code)),
                    'li');
            }
            echo '</ul>';
            echo '</span>';
            echo '</div>';
        }
    }
endif;
