<?php declare(strict_types=1);

class UserConfigTheme extends Themelet
{
    public function get_user_options(string $key): string
    {
        $html = "
                <p>".make_form(make_link("user_admin/reset_api_key"))."
                    <table style='width: 300px;'>
                        <tbody>
                        <tr><th colspan='2'>API Key</th></tr>
                        <tr>
                            <td>
                                $key
                            </td>
                        </tbody>
                        <tfoot>
                            <tr><td><input type='submit' value='Reset Key'></td></tr>
                        </tfoot>
                    </table>
                </form>
            ";
        return $html;
    }
}
