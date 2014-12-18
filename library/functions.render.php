<?php defined('APPLICATION') or exit();

if (!function_exists('ArticleUrl')) {
    /**
     * Get the URL of an article.
     *
     * @param mixed $Article
     * @param int|string $Page
     * @param bool $WithDomain
     * @return string
     */
    function ArticleUrl($Article, $Page = '', $WithDomain = true) {
        // If $Article type is an array, then cast it to an object.
        if (is_array($Article))
            $Article = (object)$Article;

        // Set up the initial URL string.
        $Result = '/article/' . Gdn_Format::Date($Article->DateInserted, '%Y') . '/' . $Article->UrlCode;

        // Add in the page number if necessary.
        if ($Page && ($Page > 1 || Gdn::Session()->UserID))
            $Result .= '/p' . $Page;

        return Url($Result, $WithDomain);
    }
}

if (!function_exists('ArticleCommentUrl')) {
    /**
     * Get the URL for an article comment.
     *
     * @param mixed $Article
     * @param int $ArticleCommentID
     * @return string
     */
    function ArticleCommentUrl($Article, $ArticleCommentID) {
        return Url("/article/comment/$ArticleCommentID/#Comment_$ArticleCommentID", true);
    }
}

if (!function_exists('FormatArticleBody')) {
    /**
     * Formats the body string of an article.
     *
     * @param string $ArticleBody
     * @param string $Format
     * @return string
     */
    function FormatArticleBody($ArticleBody, $Format = 'Html') {
        if (strcasecmp($Format, 'Html') == 0) {
            // Format links and links to videos.
            $ArticleBody = Gdn_Format::Links($ArticleBody);

            // Mentions and hashes.
            $ArticleBody = Gdn_Format::Mentions($ArticleBody);

            // Format new lines.
            $ArticleBody = preg_replace("/(\015\012)|(\015)|(\012)/", "<br />", $ArticleBody);
            $ArticleBody = FixNl2Br($ArticleBody);

            // Convert br to paragraphs.
            $ArticleBody = preg_replace('#(?:<br\s*/?>\s*?){2,}#', '</p><p>', $ArticleBody);
            // Add p on first paragraph.
            $ArticleBody = '<p>' . $ArticleBody . '</p>';
        } else {
            $ArticleBody = Gdn_Format::To($ArticleBody, $Format);
        }

        return $ArticleBody;
    }
}

if (!function_exists('ArticleCategoryUrl')) {
    /**
     * Get the URL of an article category.
     *
     * @param mixed $Category
     * @param int|string $Page
     * @param bool $WithDomain
     * @return string
     */
    function ArticleCategoryUrl($Category, $Page = '', $WithDomain = true) {
        // If $Article type is an array, then cast it to an object.
        if (is_array($Category))
            $Category = (object)$Category;

        // Set up the initial URL string.
        $Result = '/articles/category/' . $Category->UrlCode;

        // Add in the page number if necessary.
        if ($Page && ($Page > 1 || Gdn::Session()->UserID))
            $Result .= '/p' . $Page;

        return Url($Result, $WithDomain);
    }
}

if (!function_exists('ArticleAuthorAnchor')) {
    /**
     * Get the URL for the author of an article.
     */
    function ArticleAuthorAnchor($User, $CssClass = null, $Options = null) {
        static $NameUnique = NULL;
        if ($NameUnique === NULL)
            $NameUnique = C('Garden.Registration.NameUnique');

        if (is_array($CssClass)) {
            $Options = $CssClass;
            $CssClass = NULL;
        } elseif (is_string($Options))
            $Options = array('Px' => $Options);

        $Px = GetValue('Px', $Options, '');

        $Name = GetValue($Px.'Name', $User, T('Unknown'));
        $UserID = GetValue($Px.'UserID', $User, 0);

        $AuthorMeta = UserModel::GetMeta($User->UserID, 'Articles.%', 'Articles.');
        if ($AuthorMeta['AuthorDisplayName'] != "")
            $Text = $AuthorMeta['AuthorDisplayName'];
        else
            $Text = GetValue('Text', $Options, htmlspecialchars($Name)); // Allow anchor text to be overridden.

        $Attributes = array(
            'class' => $CssClass,
            'rel' => GetValue('Rel', $Options)
        );

        $UserUrl = 'profile/articles/' . $UserID . '/' . $Name;
        return '<a href="'.htmlspecialchars(Url($UserUrl)).'"'.Attribute($Attributes).'>'.$Text.'</a>';
    }
}
