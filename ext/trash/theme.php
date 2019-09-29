<?php

class TrashTheme extends Themelet
{
    public function get_image_admin_html(int $image_id)
    {
        $html = "
			".make_form(make_link('trash_restore/'.$image_id), 'POST')."
				<input type='hidden' name='image_id' value='$image_id'>
				<input type='submit' value='Restore From Trash'>
			</form>
		";

        return $html;
    }


    public function get_help_html()
    {
        return '<p>Search for images in the trash.</p>
        <div class="command_example">
        <pre>in:trash</pre>
        <p>Returns images that are in the trash.</p>
        </div> 
        ';
    }
}
