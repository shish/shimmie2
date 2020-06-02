<?php declare(strict_types=1);
use function MicroHTML\BR;
use function MicroHTML\BUTTON;
use function MicroHTML\INPUT;

class PrivateImageTheme extends Themelet
{
    public function get_image_admin_html(Image $image)
    {
        if ($image->private===false) {
            $html = SHM_SIMPLE_FORM(
                'privatize_image/'.$image->id,
                INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image->id]),
                SHM_SUBMIT("Make Private")
            );
        } else {
            $html = SHM_SIMPLE_FORM(
                'publicize_image/'.$image->id,
                INPUT(["type"=>'hidden', "name"=>'image_id', "value"=>$image->id]),
                SHM_SUBMIT("Make Public")
            );
        }

        return (string)$html;
    }


    public function get_help_html()
    {
        return '<p>Search for images that are private/public.</p>
        <div class="command_example">
        <pre>private:yes</pre>
        <p>Returns images that are private, restricted to yourself if you are not an admin.</p>
        </div>
        <div class="command_example">
        <pre>private:no</pre>
        <p>Returns images that are public.</p>
        </div>
        ';
    }

    public function get_user_options(User $user, bool $set_by_default, bool $view_by_default): string
    {
        $html = "
                <p>".make_form(make_link("user_admin/private_image"))."
                    <input type='hidden' name='id' value='$user->id'>
                    <table style='width: 300px;'>
                        <tbody>
                        <tr><th colspan='2'>Private Images</th></tr>
                        <tr>
                            <td>
                                <label><input type='checkbox' name='set_default' value='true' " .($set_by_default ? 'checked=checked': ''). " />Mark images private by default</label>
                            </td>
                        </tr><tr>
                            <td>
                                <label><input type='checkbox' name='view_default' value='true' " .($view_by_default ? 'checked=checked': ''). "  />View private images by default</label>
                            </td>
                        </tr>
                        </tbody>
                        <tfoot>
                            <tr><td><input type='submit' value='Save'></td></tr>
                        </tfoot>
                    </table>
                </form>
            ";
        return $html;
    }
}
