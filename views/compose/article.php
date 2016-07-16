<?php defined('APPLICATION') or exit();

// Declare variables.
$categories = $this->data('Categories');

// Open the form.
echo $this->Form->open(array('id' => 'Form_ComposeArticle'));
echo $this->Form->errors();
?>
    <div>
        <h1 class="H"><?php echo $this->data('Title'); ?></h1>

        <?php
        if ($categories->numRows() > 0) {
            echo '<div class="P">';
            echo $this->Form->label('Category', 'ArticleCategoryID'), ' ';
            echo $this->Form->dropDown('ArticleCategoryID', $categories, array(
                'IncludeNull' => true,
                'ValueField' => 'ArticleCategoryID',
                'TextField' => 'Name',
                'Value' => val('ArticleCategoryID', $this->data('Category'))
            ));
            echo '</div>';
        }
        ?>

        <div class="P">
            <?php
            echo $this->Form->label('Article Name', 'Name');
            echo wrap($this->Form->textBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div',
                array('class' => 'TextBoxWrapper'));
            ?>
        </div>

        <div id="UrlCode">
            <?php
            echo wrap('URL Code', 'strong') . ': ';
            echo wrap(htmlspecialchars($this->Form->getValue('UrlCode')));
            echo $this->Form->textBox('UrlCode');
            echo anchor(t('edit'), '#', 'Edit');
            echo anchor(t('OK'), '#', 'Save SmallButton');
            ?>
        </div>

        <div class="P">
            <?php
            echo $this->Form->label('Body', 'Body');
            echo $this->Form->bodyBox('Body', array('Table' => 'Article'));
            ?>
        </div>

        <div class="P">
            <?php
            echo $this->Form->label('Upload an Image', 'UploadImage');
            echo $this->Form->imageUpload('UploadImage');
            ?>

            <div id="UploadedImages">
                <?php
                if ($this->data('Article')) {
                    $uploadedImages = $this->data('UploadedImages');

                    if ($uploadedImages->numRows() > 0) {
                        $uploadedImagesResult = $uploadedImages->result();

                        foreach ($uploadedImages as $uploadedImage) {
                            $imagePath = Gdn_UploadImage::url($uploadedImage->Path);

                            echo '<div id="ArticleMedia_' . $uploadedImage->ArticleMediaID . '" class="UploadedImageWrap">' .
                                '<div class="UploadedImage"><img src="' . $imagePath . '" alt="" /></div>' .
                                '<div class="UploadedImageActions"><a class="UploadedImageInsert" href="' . $imagePath . '">Insert into Post</a>' .
                                '<br /><a class="UploadedImageDelete" href="' . url('/articles/compose/deleteimage/'
                                    . $uploadedImage->ArticleMediaID) . '?DeliveryMethod=JSON&DeliveryType=BOOL">Delete</a></div></div>';
                        }
                    }
                }
                ?>
            </div>
        </div>

        <div class="P">
            <?php
            echo $this->Form->label('Upload a Thumbnail (Max dimensions: ' . c('Articles.Articles.ThumbnailWidth', 280)
                . 'x' . c('Articles.Articles.ThumbnailHeight', 200) . ')', 'UploadThumbnail');
            echo $this->Form->imageUpload('UploadThumbnail');
            ?>

            <div id="UploadedThumbnail">
                <?php
                if ($this->data('Article')) {
                    $uploadedThumbnail = $this->data('UploadedThumbnail');

                    if ($uploadedThumbnail) {
                        $imagePath = Gdn_UploadImage::url($uploadedThumbnail->Path);

                        echo '<div id="ArticleMedia_' . $uploadedThumbnail->ArticleMediaID . '" class="UploadedImageWrap">' .
                            '<div class="UploadedImage"><img src="' . $imagePath . '" alt="" /></div>' .
                            '<div class="UploadedImageActions"><a class="UploadedImageInsert" href="' . $imagePath . '">Insert into Post</a>' .
                            '<br /><a class="UploadedImageDelete" href="' . url('/articles/compose/deleteimage/'
                                . $uploadedThumbnail->ArticleMediaID) . '?DeliveryMethod=JSON&DeliveryType=BOOL">Delete</a></div></div>';
                    }
                }
                ?>
            </div>
        </div>

        <div class="P">
            <?php
            echo $this->Form->label('Excerpt (Optional)', 'Excerpt');
            echo $this->Form->textBox('Excerpt', array('MultiLine' => true));
            ?>
        </div>

        <?php if (Gdn::session()->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')): ?>
            <div class="P">
                <?php
                echo $this->Form->label('Author', 'AuthorUserName');
                echo wrap($this->Form->textBox('AuthorUserName', array('class' => 'InputBox BigInput MultiComplete')),
                    'div', array('class' => 'TextBoxWrapper'));
                ?>
            </div>
        <?php endif; ?>

        <div class="P">
            <?php
            echo $this->Form->label('Status', 'Status');
            echo $this->Form->radioList('Status', $this->data('StatusOptions'),
                array('Default' => ArticleModel::STATUS_DRAFT));
            ?>
        </div>
    </div>

    <div class="Buttons">
        <?php
        $this->fireEvent('BeforeFormButtons');

        echo $this->Form->button((property_exists($this, 'Article')) ? 'Save' : 'Post Article',
            array('class' => 'Button Primary ArticleButton'));

        echo $this->Form->button('Preview', array('class' => 'Button PreviewButton'));

        echo anchor(t('Cancel'), '/compose/posts', 'Button ComposeCancel');

        $this->fireEvent('AfterFormButtons');
        ?>
    </div>
<?php
echo $this->Form->close();
