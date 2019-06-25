<?php

class TrashTheme extends Themelet
{
    function get_image_admin_html(int $image_id) {
        $html = "
			".make_form(make_link('trash_restore/'.$image_id), 'POST')."
				<input type='hidden' name='image_id' value='$image_id'>
				<input type='submit' value='Restore From Trash'>
			</form>
		";

        return $html;    }
}
