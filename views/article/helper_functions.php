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

if (!function_exists('writeComment')) {
    function writeComment($comment, &$currentOffset) {
        $controller = Gdn::controller();
        $session = Gdn::session();

        $cssClass = 'Item Alt ItemComment';

        $user = false;
        if (is_numeric($comment->InsertUserID)) {
            $user = Gdn::userModel()->getID($comment->InsertUserID);
        }

        // Get user meta for articles app.
        $userMeta = Gdn::userModel()->getMeta($user->UserID, 'Articles.%', 'Articles.');
        $authorDisplayName = false;
        if (isset($userMeta['AuthorDisplayName'])) {
            $authorDisplayName = $userMeta['AuthorDisplayName'];
        }

        $parentArticleCommentID = is_numeric($comment->ParentArticleCommentID) ? $comment->ParentArticleCommentID : false;
        if ($parentArticleCommentID) {
            $cssClass .= ' ItemCommentReply';
        }
        ?>
        <li class="<?php echo $cssClass; ?>" id="Comment_<?php echo $comment->ArticleCommentID; ?>">
            <div class="Comment">
                <?php writeCommentOptions($comment); ?>

                <div class="Item-Header CommentHeader">
                    <div class="AuthorWrap">
                                <span class="Author">
                                    <?php
                                    if ($user) {
                                        echo userPhoto($user);
                                        echo userAnchor($user, 'Username');

                                        if (($authorDisplayName != '') && ($authorDisplayName != $user->Name)) {
                                            echo ' (' . $authorDisplayName . ')';
                                        }

                                        $controller->fireEvent('AuthorPhoto');
                                    } else {
                                        echo wrap($comment->GuestName, 'span', array('class' => 'Username GuestName'));
                                    }
                                    ?>
                                </span>
                        <span class="AuthorInfo">
                                    <?php
                                    echo ' ' . wrapIf(htmlspecialchars(val('Title', $user)), 'span',
                                            array('class' => 'MItem AuthorTitle'));
                                    echo ' ' . wrapIf(htmlspecialchars(val('Location', $user)), 'span',
                                            array('class' => 'MItem AuthorLocation'));

                                    $controller->fireEvent('AuthorInfo');
                                    ?>
                                </span>
                    </div>

                    <div class="Meta CommentMeta CommentInfo">
                                <span
                                        class="MItem DateCreated"><?php echo anchor(Gdn_Format::date($comment->DateInserted,
                                        'html'), articleCommentUrl($comment->ArticleCommentID), 'Permalink',
                                        array('name' => 'Item_' . ($currentOffset), 'rel' => 'nofollow')); ?></span>
                        <?php
                        echo dateUpdated($comment, array('<span class="MItem">', '</span>'));

                        // Include IP Address if we have permission
                        if ($session->checkPermission('Garden.Moderation.Manage')) {
                            echo wrap(ipAnchor($comment->InsertIPAddress), 'span',
                                array('class' => 'MItem IPAddress'));
                        }
                        ?>
                    </div>
                </div>
                <div class="Item-BodyWrap">
                    <div class="Item-Body">
                        <div class="Message">
                            <?php
                            // DEPRECATED ARGUMENTS (as of 2.1)
                            // $Comment->FormatBody, Object, and Type event args
                            // added on 2014-09-12 for Emotify support.
                            $comment->FormatBody = Gdn_Format::to($comment->Body, $comment->Format);
                            $controller->EventArguments['Object'] = &$comment;
                            $controller->EventArguments['Type'] = 'ArticleComment';

                            $controller->fireEvent('BeforeCommentBody');
                            echo $comment->FormatBody;
                            $controller->fireEvent('AfterCommentFormat');
                            ?>
                        </div>
                        <?php
                        $controller->fireEvent('AfterCommentBody');
                        writeArticleReactions($comment);
                        ?>
                    </div>
                </div>
            </div>
        </li>
        <?php
        $currentOffset++;

        // Retrieve threaded comment replies and, if any, display them.
        if (!$parentArticleCommentID) {
            $repliesData = $controller->ArticleCommentModel->getRepliesByID($comment->ArticleCommentID);

            if ($repliesData->numRows() > 0) {
                $replies = $repliesData->result();

                foreach ($replies as $reply) {
                    writeComment($reply, $currentOffset);
                }
            }
        }
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
