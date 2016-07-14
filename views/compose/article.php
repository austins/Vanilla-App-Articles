<?php defined('APPLICATION') or exit();

// Declare variables.
$Categories = $this->data('Categories');

// Open the form.
echo $this->Form->Open(array('id' => 'Form_ComposeArticle'));
echo $this->Form->Errors();
?>
    <div>
        <h1 class="H"><?php echo $this->data('Title'); ?></h1>

        <?php
        if ($Categories->numRows() > 0) {
            echo '<div class="P">';
            echo $this->Form->Label('Category', 'ArticleCategoryID'), ' ';
            echo $this->Form->DropDown('ArticleCategoryID', $Categories, array(
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
            echo $this->Form->Label('Article Name', 'Name');
            echo Wrap($this->Form->TextBox('Name', array('maxlength' => 100, 'class' => 'InputBox BigInput')), 'div',
                array('class' => 'TextBoxWrapper'));
            ?>
        </div>

        <div id="UrlCode">
            <?php
            echo Wrap('URL Code', 'strong') . ': ';
            echo Wrap(htmlspecialchars($this->Form->GetValue('UrlCode')));
            echo $this->Form->TextBox('UrlCode');
            echo Anchor(T('edit'), '#', 'Edit');
            echo Anchor(T('OK'), '#', 'Save SmallButton');
            ?>
        </div>

        <div class="P">
            <?php
            echo $this->Form->Label('Body', 'Body');
            echo $this->Form->BodyBox('Body', array('Table' => 'Article'));
            ?>
        </div>

        <div class="P">
            <?php
            echo $this->Form->Label('Upload an Image', 'UploadImage');
            echo $this->Form->ImageUpload('UploadImage');
            ?>

            <div id="UploadedImages">
                <?php
                if ($this->data('Article')) {
                    $UploadedImages = $this->data('UploadedImages');

                    if ($UploadedImages->numRows() > 0) {
                        $UploadedImagesResult = $UploadedImages->result();

                        foreach ($UploadedImages as $UploadedImage) {
                            $ImagePath = Gdn_UploadImage::url($UploadedImage->Path);

                            echo '<div id="ArticleMedia_' . $UploadedImage->ArticleMediaID . '" class="UploadedImageWrap">' .
                                '<div class="UploadedImage"><img src="' . $ImagePath . '" alt="" /></div>' .
                                '<div class="UploadedImageActions"><a class="UploadedImageInsert" href="' . $ImagePath . '">Insert into Post</a>' .
                                '<br /><a class="UploadedImageDelete" href="' . url('/articles/compose/deleteimage/'
                                    . $UploadedImage->ArticleMediaID) . '?DeliveryMethod=JSON&DeliveryType=BOOL">Delete</a></div></div>';
                        }
                    }
                }
                ?>
            </div>
        </div>

        <div class="P">
            <?php
            echo $this->Form->Label('Upload a Thumbnail (Max dimensions: ' . c('Articles.Articles.ThumbnailWidth', 280)
                . 'x' . c('Articles.Articles.ThumbnailHeight', 200) . ')', 'UploadThumbnail');
            echo $this->Form->ImageUpload('UploadThumbnail');
            ?>

            <div id="UploadedThumbnail">
                <?php
                if ($this->data('Article')) {
                    $UploadedThumbnail = $this->data('UploadedThumbnail');

                    if ($UploadedThumbnail) {
                        $ImagePath = Gdn_UploadImage::url($UploadedThumbnail->Path);

                        echo '<div id="ArticleMedia_' . $UploadedThumbnail->ArticleMediaID . '" class="UploadedImageWrap">' .
                            '<div class="UploadedImage"><img src="' . $ImagePath . '" alt="" /></div>' .
                            '<div class="UploadedImageActions"><a class="UploadedImageInsert" href="' . $ImagePath . '">Insert into Post</a>' .
                            '<br /><a class="UploadedImageDelete" href="' . url('/articles/compose/deleteimage/'
                                . $UploadedThumbnail->ArticleMediaID) . '?DeliveryMethod=JSON&DeliveryType=BOOL">Delete</a></div></div>';
                    }
                }
                ?>
            </div>
        </div>

        <div class="P">
            <?php
            echo $this->Form->Label('Excerpt (Optional)', 'Excerpt');
            echo $this->Form->TextBox('Excerpt', array('MultiLine' => TRUE));
            ?>
        </div>

        <?php if (Gdn::session()->checkPermission('Articles.Articles.Edit', true, 'ArticleCategory', 'any')): ?>
            <div class="P">
                <?php
                echo $this->Form->Label('Author', 'AuthorUserName');
                echo Wrap($this->Form->TextBox('AuthorUserName', array('class' => 'InputBox BigInput MultiComplete')),
                    'div', array('class' => 'TextBoxWrapper'));
                ?>
            </div>
        <?php endif; ?>

        <div class="P">
            <?php
            echo $this->Form->Label('Status', 'Status');
            echo $this->Form->RadioList('Status', $this->data('StatusOptions'),
                array('Default' => ArticleModel::STATUS_DRAFT));
            ?>
        </div>
    </div>

    <div class="Buttons">
        <?php
        $this->fireEvent('BeforeFormButtons');

        echo $this->Form->Button((property_exists($this, 'Article')) ? 'Save' : 'Post Article',
            array('class' => 'Button Primary ArticleButton'));

        echo $this->Form->Button('Preview', array('class' => 'Button PreviewButton'));

        echo Anchor(T('Cancel'), '/compose/posts', 'Button ComposeCancel');

        $this->fireEvent('AfterFormButtons');
        ?>
    </div>
<?php
echo $this->Form->Close();
