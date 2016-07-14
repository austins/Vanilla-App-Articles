<?php defined('APPLICATION') or exit();
/**
 * Copyright (C) 2015  Austin S.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if (!function_exists('articleUrl')) {
    /**
     * Get the URL of an article.
     *
     * @param object|array $Article
     * @param int|string $Page
     * @param bool $WithDomain
     * @return string
     */
    function articleUrl($Article, $Page = '', $WithDomain = true) {
        // If $Article type is an array, then cast it to an object.
        if (is_array($Article))
            $Article = (object)$Article;

        // Set up the initial URL string.
        $Result = '/article/' . Gdn_Format::date($Article->DateInserted, '%Y') . '/' . $Article->UrlCode;

        // Add in the page number if necessary.
        if ($Page && ($Page > 1 || Gdn::session()->UserID))
            $Result .= '/p' . $Page;

        return url($Result, $WithDomain);
    }
}

if (!function_exists('articleCommentUrl')) {
    /**
     * Get the URL for an article comment.
     *
     * @param int $ArticleCommentID
     * @return string
     */
    function articleCommentUrl($ArticleCommentID) {
        return url("/article/comment/$ArticleCommentID/#Comment_$ArticleCommentID", true);
    }
}

if (!function_exists('formatArticleBody')) {
    /**
     * Formats the body string of an article.
     *
     * @param string $ArticleBody
     * @param string $Format
     * @return string
     */
    function formatArticleBody($ArticleBody, $Format = 'Html') {
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

if (!function_exists('articleCategoryUrl')) {
    /**
     * Get the URL of an article category.
     *
     * @param object|array|string $Category
     * @param int|string $Page
     * @param bool $WithDomain
     * @return string
     */
    function articleCategoryUrl($Category, $Page = '', $WithDomain = true) {
        // If $Category type is an array, then cast it to an object.
        if (is_array($Category))
            $Category = (object)$Category;

        // If $Category is an object, then assign $UrlCode to UrlCode property,
        // else assume $Category is a string that is the UrlCode.
        $UrlCode = isset($Category->UrlCode) ? $Category->UrlCode : $Category;

        // Set up the initial URL string.
        $Result = '/articles/category/' . $UrlCode;

        // Add in the page number if necessary.
        if ($Page && ($Page > 1 || Gdn::session()->UserID))
            $Result .= '/p' . $Page;

        return url($Result, $WithDomain);
    }
}

if (!function_exists('articleAuthorAnchor')) {
    /**
     * Get the URL for the author of an article.
     */
    function articleAuthorAnchor($User, $CssClass = null, $Options = null) {
        static $NameUnique = NULL;
        if ($NameUnique === NULL)
            $NameUnique = c('Garden.Registration.NameUnique');

        if (is_array($CssClass)) {
            $Options = $CssClass;
            $CssClass = NULL;
        } elseif (is_string($Options))
            $Options = array('Px' => $Options);

        $Px = GetValue('Px', $Options, '');

        $Name = GetValue($Px.'Name', $User, T('Unknown'));
        $UserID = GetValue($Px.'UserID', $User, 0);

        $AuthorMeta = UserModel::GetMeta($User->UserID, 'Articles.%', 'Articles.');
        if (isset($AuthorMeta['AuthorDisplayName']) && $AuthorMeta['AuthorDisplayName'] != "")
            $Text = $AuthorMeta['AuthorDisplayName'];
        else
            $Text = GetValue('Text', $Options, htmlspecialchars($Name)); // Allow anchor text to be overridden.

        $Attributes = array(
            'class' => $CssClass,
            'rel' => GetValue('Rel', $Options)
        );

        $UserUrl = 'profile/articles/' . $UserID . '/' . $Name;
        return '<a href="'.htmlspecialchars(url($UserUrl)).'"'.Attribute($Attributes).'>'.$Text.'</a>';
    }
}
