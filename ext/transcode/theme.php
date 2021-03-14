<?php declare(strict_types=1);

class TranscodeImageTheme extends Themelet
{
    /*
     * Display a link to resize an image
     */
    public function get_transcode_html(Image $image, array $options): string
    {
        $html = "
			".make_form(
            make_link("transcode/{$image->id}"),
            'POST',
            false,
            "",
            "return transcodeSubmit()"
        )."
                <input type='hidden' name='image_id' value='{$image->id}'>
                <input type='hidden' id='image_lossless' name='image_lossless' value='{$image->lossless}'>
                ".$this->get_transcode_picker_html($options)."
				<br><input id='transcodebutton' type='submit' value='Transcode Image'>
			</form>
		";

        return $html;
    }

    public function get_transcode_picker_html(array $options): string
    {
        $html = "<select id='transcode_mime'  name='transcode_mime' required='required' >";
        foreach ($options as $display=>$value) {
            $html .= "<option value='$value'>$display</option>";
        }

        return $html."</select>";
    }

    public function display_transcode_error(Page $page, string $title, string $message): void
    {
        $page->set_title("Transcode Image");
        $page->set_heading("Transcode Image");
        $page->add_block(new NavBlock());
        $page->add_block(new Block($title, $message));
    }
}
