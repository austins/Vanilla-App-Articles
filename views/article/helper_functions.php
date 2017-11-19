<?php defined('APPLICATION') or exit();

if (!function_exists('showArticleOptions')) {
    function showArticleOptions($article) {
        // If $Article type is an array, then cast it to an object.
        if (is_array($article)) {
            $article = (object)$article;
        }

        $sender = Gdn::controller();
        $session = Gdn::session();
        $options = array();

        // Can the user edit?
        if (ArticleModel::canEdit($article)) {
            $options['EditArticle'] = array(
                'Label' => t('Edit'),
                'Url' => '/compose/editarticle/' . $article->ArticleID);
        }

        // Can the user close?
        if ($session->checkPermission('Articles.Articles.Close', true, 'ArticleCategory',
            $article->PermissionArticleCategoryID)
        ) {
            $newClosed = (int)!$article->Closed;
            $options['CloseArticle'] = array(
                'Label' => t($article->Closed ? 'Reopen' : 'Close'),
                'Url' => "/article/close/{$article->ArticleID}?close={$newClosed}",
                'Class' => 'Hijack');
        }

        // Can the user delete?
        if ($session->checkPermission('Articles.Articles.Delete', true, 'ArticleCategory',
            $article->PermissionArticleCategoryID)
        ) {
            $articleCategoryModel = new ArticleCategoryModel();
            $category = $articleCategoryModel->getByID(val('ArticleCategoryID', $article));

            $options['DeleteArticle'] = array(
                'Label' => t('Delete'),
                'Url' => '/article/delete/' . $article->ArticleID,
                'Class' => 'DeleteArticle Popup');

            if (strtolower($sender->ControllerName) === "articlecontroller") {
                $options['DeleteArticle']['Url'] .= '?&target=' . urlencode(articleCategoryUrl($category));
            }
        }

        // Render the article options menu.
        if (!empty($options)) {
            echo '<div class="Options">';
            echo '<span class="ToggleFlyout OptionsMenu">';
            echo '<span class="OptionsTitle" title="' . t('Options') . '">' . t('Options') . '</span>';
            echo sprite('SpFlyoutHandle', 'Arrow');
            echo '<ul class="Flyout MenuItems">';
            foreach ($options as $code => $option) {
                echo wrap(anchor($option['Label'], $option['Url'], val('Class', $option, $code)), 'li');
            }
            echo '</ul>';
            echo '</span>';
            echo '</div>';
        }
    }
}

if (!function_exists('articleTag')) {
    function articleTag($article, $column, $code, $cssClass = false) {
        if (is_array($article)) {
            $article = (object)$article;
        }

        if ((is_numeric($article->$column) && !$article->$column)
            || (!is_numeric($article->$column) && strcasecmp($article->$column, $code) != 0)
        ) {
            return '';
        }

        if (!$cssClass) {
            $cssClass = "Tag-$code";
        }

        return ' <span class="Tag ' . $cssClass . '" title="' . htmlspecialchars(t($code)) . '">' . t($code) . '</span> ';
    }
}

if (!function_exists('showCommentForm')) {
    function showCommentForm() {
        $session = Gdn::session();
        $controller = Gdn::controller();
        $article = $controller->Article;
        $userCanClose = $session->checkPermission('Articles.Articles.Close', true, 'ArticleCategory',
            $article->PermissionArticleCategoryID);
        $userCanComment = $session->checkPermission('Articles.Comments.Add', true, 'ArticleCategory',
            $article->PermissionArticleCategoryID);
        $canGuestsComment = c('Articles.Comments.AllowGuests', false);

        // Closed notification
        if ((bool)$article->Closed) {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed"><?php echo t('This article has been closed.'); ?></div>
            </div>
            <?php
        } else if (!$session->isValid() && !$canGuestsComment) {
            ?>
            <div class="Foot Closed">
                <div class="Note Closed SignInOrRegister"><?php
                    $popup = (c('Garden.SignIn.Popup')) ? ' class="Popup"' : '';
                    echo formatString(
                        t('Sign In or Register to Comment.',
                            '<a href="{SignInUrl,html}"{Popup}>Sign In</a> or <a href="{RegisterUrl,html}">Register</a> to comment.'),
                        array(
                            'SignInUrl' => url(signInUrl(url(''))),
                            'RegisterUrl' => url(registerUrl(url(''))),
                            'Popup' => $popup
                        )
                    ); ?>
                </div>
            </div>
            <?php
        }

        if ((($article->Closed == '1') && $userCanClose)
            || (($article->Closed == '0') && $userCanComment)
            || (!$session->isValid() && $canGuestsComment)
        ) {
            echo $controller->fetchView('comment', 'compose', 'Articles');
        }
    }
}

if (!function_exists('writeArticleReactions')):
    function writeArticleReactions($comment, $type = 'Comment') {
        list($recordType, $recordID) = recordType($comment);

        Gdn::controller()->EventArguments['RecordType'] = strtolower($recordType);
        Gdn::controller()->EventArguments['RecordID'] = $recordID;

        echo '<div class="Reactions">';
        Gdn_Theme::bulletRow();

        $session = Gdn::session();
        $guestCommenting = (c('Articles.Comments.AllowGuests', false) && !$session->isValid());
        if (c('Articles.Comments.EnableThreadedComments', true) && !$comment->ParentArticleCommentID) {
            if ($session->isValid() || $guestCommenting) {
                echo anchor('<span class="ReactSprite ReactReply"></span> Reply',
                    '/compose/comment/' . $comment->ArticleID . '/' . $comment->ArticleCommentID,
                    'ReactButton ReplyLink Visible');
            }
        }

        Gdn::controller()->fireEvent('AfterArticleReactions');
        echo '</div>';
    }
endif;

if (!function_exists('getCommentOptions')):
    function getCommentOptions($comment) {
        $options = array();

        if (!is_numeric(val('ArticleCommentID', $comment))) {
            return $options;
        }

        $sender = Gdn::controller();
        $session = Gdn::session();

        $article = &$sender->Article;
        $articleCategoryID = val('ArticleCategoryID', $article);

        // Determine if we still have time to edit
        $editContentTimeout = c('Garden.EditContentTimeout', -1);
        $canEdit = $editContentTimeout == -1 || strtotime($comment->DateInserted) + $editContentTimeout > time();

        // Don't allow guests to edit.
        if (!$session->isValid()) {
            $canEdit = false;
        }

        $timeLeft = '';

        if ($canEdit && $editContentTimeout > 0 && !$session->checkPermission('Articles.Articles.Edit', true,
                'ArticleCategory', $article->PermissionArticleCategoryID)
        ) {
            $timeLeft = strtotime($comment->DateInserted) + $editContentTimeout - time();
            $timeLeft = $timeLeft > 0 ? ' (' . Gdn_Format::seconds($timeLeft) . ')' : '';
        }

        // Can the user edit the comment?
        if (($canEdit && $session->UserID == $comment->InsertUserID) || $session->checkPermission('Articles.Comments.Edit',
                true, 'ArticleCategory', $article->PermissionArticleCategoryID)
        ) {
            $options['EditComment'] = array('Label' => t('Edit') . ' ' . $timeLeft,
                'Url' => '/articles/compose/editcomment/' . $comment->ArticleCommentID, 'EditComment');
        }

        // Can the user delete the comment?
        $selfDeleting = ($canEdit && $session->UserID == $comment->InsertUserID && c('Articles.Comments.AllowSelfDelete'));
        if ($selfDeleting || $session->checkPermission('Articles.Comments.Delete', true, 'ArticleCategory',
                $article->PermissionArticleCategoryID)
        ) {
            $options['DeleteComment'] = array('Label' => t('Delete'),
                'Url' => '/articles/article/deletecomment/' . $comment->ArticleCommentID . '/' . $session->transientKey()
                    . '/?Target=' . urlencode('/article/' . Gdn_Format::date($article->DateInserted, '%Y') . '/'
                        . $article->UrlCode), 'Class' => 'DeleteComment');
        }

        // Allow plugins to add options
        $sender->EventArguments['CommentOptions'] = &$options;
        $sender->EventArguments['Comment'] = $comment;
        $sender->fireEvent('CommentOptions');

        return $options;
    }
endif;

if (!function_exists('writeCommentOptions')):
    function writeCommentOptions($comment) {
        $options = getCommentOptions($comment);

        if (count($options) > 0) {
            echo '<div class="Options">';
            echo '<span class="ToggleFlyout OptionsMenu">';
            echo '<span class="OptionsTitle" title="' . t('Options') . '">' . t('Options') . '</span>';
            echo sprite('SpFlyoutHandle', 'Arrow');
            echo '<ul class="Flyout MenuItems">';
            foreach ($options as $code => $option) {
                echo wrap(anchor($option['Label'], $option['Url'], val('Class', $option, $code)),
                    'li');
            }
            echo '</ul>';
            echo '</span>';
            echo '</div>';
        }
    }
endif;
