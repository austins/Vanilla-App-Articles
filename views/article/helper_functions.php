<?php
if (!defined('APPLICATION'))
    exit();

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
                'Url' => '/compose/editarticle/' . $Article->ArticleID . '/');

        // Can the user close?
        if ($Session->CheckPermission('Articles.Articles.Close')) {
            $NewClosed = (int)!$Article->Closed;
            $Options['CloseArticle'] = array(
                'Label' => T($Article->Closed ? 'Reopen' : 'Close'),
                'Url' => "/compose/closearticle?articleid={$Article->ArticleID}&close={$NewClosed}",
                'Class' => 'Hijack');
        }

        // Can the user delete?
        if ($Session->CheckPermission('Articles.Articles.Delete')) {
            $ArticleCategoryModel = new ArticleCategoryModel();
            $Category = $ArticleCategoryModel->GetByID(GetValue('CategoryID', $Article));

            $Options['DeleteArticle'] = array(
                'Label' => T('Delete'),
                'Url' => '/compose/deletearticle/' . $Article->ArticleID,
                'Class' => 'DeleteArticle Popup');

            if (strtolower($Sender->ControllerName) === "articlecontroller")
                $Options['DeleteArticle']['Url'] .= '?&target=' . urlencode(ArticleCategoryUrl($Category));
            else
                $Options['DeleteArticle']['Url'] .= '/';
        }

        // Render the article options menu.
        if (!empty($Options)) {
            echo '<div class="Options">';
            echo '<span class="ToggleFlyout OptionsMenu">';
            echo '<span class="OptionsTitle" title="' . T('Options') . '">' . T('Options') . '</span>';
            echo Sprite('SpFlyoutHandle', 'Arrow');
            echo '<ul class="Flyout MenuItems" style="display: none;">';
            foreach ($Options as $Code => $Option) {
                echo Wrap(Anchor($Option['Label'], $Option['Url'], GetValue('Class', $Option, $Code)), 'li');
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

        // Closed notification
        if ((bool)$Article->Closed) {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed"><?php echo T('This article has been closed.'); ?></div>
            </div>
        <?php
        } else if (!$UserCanComment) {
            if (!Gdn::Session()->IsValid()) {
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
        }

        if ((($Article->Closed == '1') && $UserCanClose) || (($Article->Closed == '0') && $UserCanComment))
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

        if (C('Articles.Articles.EnableThreadedComments', true) && !$Comment->ParentCommentID)
            echo Anchor('<span class="ReactSprite ReactReply"></span> Reply',
                '/compose/comment/' . $Comment->ArticleID . '/' . $Comment->CommentID . '/',
                'ReactButton ReplyLink Visible');

        Gdn::Controller()->FireEvent('AfterFlag');
        Gdn::Controller()->FireEvent('AfterReactions');
        echo '</div>';
    }
endif;
