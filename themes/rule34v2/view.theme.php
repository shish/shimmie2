<?php

declare(strict_types=1);

namespace Shimmie2;

use MicroHTML\HTMLElement;

use function MicroHTML\{TR, TH, TD, emptyHTML, rawHTML, joinHTML, DIV, INPUT, A};

class CustomViewImageTheme extends ViewImageTheme
{
    // override to make info box always in edit mode
    protected function build_info(Image $image, $editor_parts): string
    {
        global $user;

        if (count($editor_parts) == 0) {
            return ($image->is_locked() ? "<br>[Post Locked]" : "");
        }

        $html = make_form(make_link("post/set"))."
					<input type='hidden' name='image_id' value='{$image->id}'>
					<table style='width: 500px; max-width: 100%;' class='image_info form'>
		";
        foreach ($editor_parts as $part) {
            $html .= $part;
        }
        if (
            (!$image->is_locked() || $user->can(Permissions::EDIT_IMAGE_LOCK)) &&
            $user->can(Permissions::EDIT_IMAGE_TAG)
        ) {
            $html .= "
						<tr><td colspan='4'>
							<input class='edit' type='submit' value='Set'>
						</td></tr>
			";
        }
        $html .= "
					</table>
				</form>
		";
        return $html;
    }
}
