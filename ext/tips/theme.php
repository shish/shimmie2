<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * @phpstan-type Tip array{id: int, image: string, text: string, enable: bool}
 */
class TipsTheme extends Themelet
{
    /**
     * @param string[] $images
     */
    public function manageTips(string $url, array $images): void
    {
        global $page;
        $select = "<select name='image'><option value=''>- Select Post -</option>";

        foreach ($images as $image) {
            $select .= "<option style='background-image:url(".$url.$image."); background-repeat:no-repeat; padding-left:20px;'  value=\"".$image."\">".$image."</option>\n";
        }

        $select .= "</select>";

        $html = "
".make_form(make_link("tips/save"))."
<table>
  <tr>
    <td>Enable:</td>
    <td><input name='enable' type='checkbox' value='Y' checked/></td>
  </tr>
  <tr>
    <td>Post:</td>
    <td>{$select}</td>
  </tr>
  <tr>
    <td>Message:</td>
    <td><textarea name='text'></textarea></td>
  </tr>
  <tr>
    <td colspan='2'><input type='submit' value='Submit' /></td>
  </tr>
</table>
</form>
";

        $page->set_title("Tips List");
        $page->set_heading("Tips List");
        $page->add_block(new NavBlock());
        $page->add_block(new Block("Add Tip", $html, "main", 10));
    }

    /**
     * @param Tip $tip
     */
    public function showTip(string $url, array $tip): void
    {
        global $page;

        $img = "";
        if (!empty($tip['image'])) {
            $img = "<img src=".$url.url_escape($tip['image'])." /> ";
        }
        $html = "<div id='tips'>".$img.html_escape($tip['text'])."</div>";
        $page->add_block(new Block(null, $html, "subheading", 10));
    }

    /**
     * @param Tip[] $tips
     */
    public function showAll(string $url, array $tips): void
    {
        global $user, $page;

        $html = "<table id='poolsList' class='zebra'>".
            "<thead><tr>".
            "<th>ID</th>".
            "<th>Enabled</th>".
            "<th>Post</th>".
            "<th>Text</th>";

        if ($user->can(Permissions::TIPS_ADMIN)) {
            $html .= "<th>Action</th>";
        }

        $html .= "</tr></thead>";

        foreach ($tips as $tip) {
            $tip_enable = bool_escape($tip['enable']) ? "Yes" : "No";
            $set_link = "<a href='".make_link("tips/status/".$tip['id'])."'>".$tip_enable."</a>";

            $html .= "<tr>".
                "<td>".$tip['id']."</td>".
                "<td>".$set_link."</td>".
                (
                    empty($tip['image']) ?
                    "<td></td>" :
                    "<td><img alt='' src=".$url.$tip['image']." /></td>"
                ).
                "<td class='left'>".$tip['text']."</td>";

            $del_link = "<a href='".make_link("tips/delete/".$tip['id'])."'>Delete</a>";

            if ($user->can(Permissions::TIPS_ADMIN)) {
                $html .= "<td>".$del_link."</td>";
            }

            $html .= "</tr>";
        }
        $html .= "</tbody></table>";

        $page->add_block(new Block("All Tips", $html, "main", 20));
    }
}
