<?php defined('APPLICATION') or exit();

if (!function_exists('showArticleOptions')) {
    function showArticleOptions($Article) {
        // If $Article type is an array, then cast it to an object.
        if (is_array($Article))
            $Article = (object)$Article;

        $Sender = Gdn::Controller();
        $session = Gdn::session();
        $Options = array();

        // Can the user edit?
        if ($session->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID))
            $Options['EditArticle'] = array(
                'Label' => t('Edit'),
                'Url' => '/compose/editarticle/' . $Article->ArticleID);

        // Can the user close?
        if ($session->checkPermission('Articles.Articles.Close', true, 'ArticleCategory', $Article->PermissionArticleCategoryID)) {
            $NewClosed = (int)!$Article->Closed;
            $Options['CloseArticle'] = array(
                'Label' => t($Article->Closed ? 'Reopen' : 'Close'),
                'Url' => "/article/close/{$Article->ArticleID}?close={$NewClosed}",
                'Class' => 'Hijack');
        }

        // Can the user delete?
        if ($session->checkPermission('Articles.Articles.Delete', true, 'ArticleCategory', $Article->PermissionArticleCategoryID)) {
            $ArticleCategoryModel = new ArticleCategoryModel();
            $Category = $ArticleCategoryModel->getByID(val('ArticleCategoryID', $Article));

            $Options['DeleteArticle'] = array(
                'Label' => t('Delete'),
                'Url' => '/article/delete/' . $Article->ArticleID,
                'Class' => 'DeleteArticle Popup');

            if (strtolower($Sender->ControllerName) === "articlecontroller")
                $Options['DeleteArticle']['Url'] .= '?&target=' . urlencode(articleCategoryUrl($Category));
        }

        // Render the article options menu.
        if (!empty($Options)) {
            echo '<div class="Options">';
            echo '<span class="ToggleFlyout OptionsMenu">';
            echo '<span class="OptionsTitle" title="' . t('Options') . '">' . t('Options') . '</span>';
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
    function articleTag($Article, $Column, $Code, $CssClass = false) {
        if (is_array($Article))
            $Article = (object)$Article;

        if ((is_numeric($Article->$Column) && !$Article->$Column)
            || (!is_numeric($Article->$Column) && strcasecmp($Article->$Column, $Code) != 0)
        )
            return '';

        if (!$CssClass)
            $CssClass = "Tag-$Code";

        return ' <span class="Tag ' . $CssClass . '" title="' . htmlspecialchars(t($Code)) . '">' . t($Code) . '</span> ';
    }
}

if (!function_exists('ShowCommentForm')) {
    function ShowCommentForm() {
        $session = Gdn::session();
        $Controller = Gdn::Controller();
        $Article = $Controller->Article;
        $UserCanClose = $session->checkPermission('Articles.Articles.Close', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
        $UserCanComment = $session->checkPermission('Articles.Comments.Add', true, 'ArticleCategory', $Article->PermissionArticleCategoryID);
        $canGuestsComment = c('Articles.Comments.AllowGuests', false);

        // Closed notification
        if ((bool)$Article->Closed) {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed"><?php echo t('This article has been closed.'); ?></div>
            </div>
        <?php
        } else if (!$session->isValid() && !$canGuestsComment) {
                ?>
                <div class="Foot Closed">
                    <div class="Note Closed SignInOrRegister"><?php
                        $Popup = (c('Garden.SignIn.Popup')) ? ' class="Popup"' : '';
                        echo FormatString(
                            t('Sign In or Register to Comment.',
                                '<a href="{SignInUrl,html}"{Popup}>Sign In</a> or <a href="{RegisterUrl,html}">Register</a> to comment.'),
                            array(
                                'SignInUrl' => url(SignInUrl(url(''))),
                                'RegisterUrl' => url(RegisterUrl(url(''))),
                                'Popup' => $Popup
                            )
                        ); ?>
                    </div>
                </div>
            <?php
        }

        if ((($Article->Closed == '1') && $UserCanClose)
                || (($Article->Closed == '0') && $UserCanComment)
                || (!$session->isValid() && $canGuestsComment))
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

        $session = Gdn::session();
        $GuestCommenting = (c('Articles.Comments.AllowGuests', false) && !$session->isValid());
        if (c('Articles.Comments.EnableThreadedComments', true) && !$Comment->ParentArticleCommentID) {
            if ($session->isValid() || $GuestCommenting) {
                echo Anchor('<span class="ReactSprite ReactReply"></span> Reply',
                    '/compose/comment/' . $Comment->ArticleID . '/' . $Comment->ArticleCommentID,
                    'ReactButton ReplyLink Visible');
            }
        }
        
        Gdn::Controller()->fireEvent('AfterArticleReactions');
        echo '</div>';
    }
endif;

if (!function_exists('GetCommentOptions')):
    function GetCommentOptions($Comment) {
        $Options = array();

        if (!is_numeric(val('ArticleCommentID', $Comment)))
            return $Options;

        $Sender = Gdn::Controller();
        $session = Gdn::session();

        $Article = & $Sender->Article;
        $ArticleCategoryID = val('ArticleCategoryID', $Article);

        // Determine if we still have time to edit
        $EditContentTimeout = c('Garden.EditContentTimeout', -1);
        $CanEdit = $EditContentTimeout == -1 || strtotime($Comment->DateInserted) + $EditContentTimeout > time();

        // Don't allow guests to edit.
        if (!$session->isValid())
            $CanEdit = false;

        $TimeLeft = '';

        if ($CanEdit && $EditContentTimeout > 0 && !$session->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID)) {
            $TimeLeft = strtotime($Comment->DateInserted) + $EditContentTimeout - time();
            $TimeLeft = $TimeLeft > 0 ? ' (' . Gdn_Format::Seconds($TimeLeft) . ')' : '';
        }

        // Can the user edit the comment?
        if (($CanEdit && $session->UserID == $Comment->InsertUserID) || $session->checkPermission('Articles.Comments.Edit', true, 'ArticleCategory', $Article->PermissionArticleCategoryID))
            $Options['EditComment'] = array('Label' => t('Edit') . ' ' . $TimeLeft,
                'Url' => '/articles/compose/editcomment/' . $Comment->ArticleCommentID, 'EditComment');

        // Can the user delete the comment?
        $SelfDeleting = ($CanEdit && $session->UserID == $Comment->InsertUserID && c('Articles.Comments.AllowSelfDelete'));
        if ($SelfDeleting || $session->checkPermission('Articles.Comments.Delete', true, 'ArticleCategory', $Article->PermissionArticleCategoryID))
            $Options['DeleteComment'] = array('Label' => t('Delete'),
                'Url' => '/articles/article/deletecomment/' . $Comment->ArticleCommentID . '/' . $session->TransientKey()
                    . '/?Target=' . urlencode('/article/' . Gdn_Format::date($Article->DateInserted, '%Y') . '/'
                        . $Article->UrlCode), 'Class' => 'DeleteComment');

        // Allow plugins to add options
        $Sender->EventArguments['CommentOptions'] = & $Options;
        $Sender->EventArguments['Comment'] = $Comment;
        $Sender->fireEvent('CommentOptions');

        return $Options;
    }
endif;

if (!function_exists('WriteCommentOptions')):
    function WriteCommentOptions($Comment) {
        $Options = GetCommentOptions($Comment);

        if (count($Options) > 0) {
            echo '<div class="Options">';
            echo '<span class="ToggleFlyout OptionsMenu">';
            echo '<span class="OptionsTitle" title="' . t('Options') . '">' . t('Options') . '</span>';
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
