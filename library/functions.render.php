<?php
/**
 * Render functions.
 *
 * @copyright 2015-2018 Austin S.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 */

if (!function_exists('articleUrl')) {
    /**
     * Get the URL of an article.
     *
     * @param object|array $article
     * @param int|string $page
     * @param bool $withDomain
     * @return string
     */
    function articleUrl($article, $page = '', $withDomain = true) {
        // Set up the initial URL string.
        $result = '/article/' . Gdn_Format::date(val('DateInserted', $article), '%Y') . '/' . val('UrlCode', $article);

        // Add in the page number if necessary.
        if ($page && $page > 1) {
            $result .= '/p' . $page;
        }

        return url($result, $withDomain);
    }
}

if (!function_exists('articleCommentUrl')) {
    /**
     * Get the URL for an article comment.
     *
     * @param int $articleCommentID
     * @return string
     */
    function articleCommentUrl($articleCommentID) {
        return url("/article/comment/$articleCommentID/#Comment_$articleCommentID", true);
    }
}

if (!function_exists('formatArticleBody')) {
    /**
     * Formats the body string of an article.
     *
     * @param string $articleBody
     * @param string $format
     * @return string
     */
    function formatArticleBody($articleBody, $format = 'Html') {
        if (strcasecmp($format, 'Html') == 0) {
            // Format links and links to videos.
            $articleBody = Gdn_Format::links($articleBody);

            // Mentions and hashes.
            $articleBody = Gdn_Format::mentions($articleBody);

            // Format new lines.
            $articleBody = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $articleBody);
            $articleBody = fixnl2br($articleBody);

            // Convert br to paragraphs.
            $articleBody = preg_replace('#(?:<br\s*/?>\s*?){2,}#', '</p><p>', $articleBody);
            // Add p on first paragraph.
            $articleBody = '<p>' . $articleBody . '</p>';
        } else {
            $articleBody = Gdn_Format::to($articleBody, $format);
        }

        return $articleBody;
    }
}

if (!function_exists('articleCategoryUrl')) {
    /**
     * Get the URL of an article category.
     *
     * @param object|array|string $category
     * @param int|string $page
     * @param bool $withDomain
     * @return string
     */
    function articleCategoryUrl($category, $page = '', $withDomain = true) {
        $urlCode = is_string($category) ? $category : val('UrlCode', $category);

        // Set up the initial URL string.
        $result = '/articles/category/' . rawurlencode($urlCode);

        // Add in the page number if necessary.
        if ($page && $page > 1) {
            $result .= '/p' . $page;
        }

        return url($result, $withDomain);
    }
}

if (!function_exists('articleAuthorAnchor')) {
    /**
     * Get the URL for the author of an article.
     *
     * @param object $user
     * @param array $cssClass
     * @param null $options
     * @return string
     */
    function articleAuthorAnchor($user, $cssClass = null, $options = null) {
        static $nameUnique = null;
        if ($nameUnique === null) {
            $nameUnique = c('Garden.Registration.NameUnique');
        }

        if (is_array($cssClass)) {
            $options = $cssClass;
            $cssClass = null;
        } elseif (is_string($options)) {
            $options = array('Px' => $options);
        }

        $px = val('Px', $options, '');

        $name = val($px . 'Name', $user, t('Unknown'));
        $userID = val($px . 'UserID', $user, 0);

        $authorMeta = UserModel::getMeta($user->UserID, 'Articles.%', 'Articles.');
        if (isset($authorMeta['AuthorDisplayName']) && $authorMeta['AuthorDisplayName'] != "") {
            $text = $authorMeta['AuthorDisplayName'];
        } else {
            $text = val('Text', $options, htmlspecialchars($name));
        } // Allow anchor text to be overridden.

        $attributes = array(
            'class' => $cssClass,
            'rel' => val('Rel', $options)
        );

        $userUrl = 'profile/articles/' . $userID . '/' . $name;

        return '<a href="' . htmlspecialchars(url($userUrl)) . '"' . attribute($attributes) . '>' . $text . '</a>';
    }
}
