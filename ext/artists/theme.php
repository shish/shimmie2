<?php

declare(strict_types=1);

namespace Shimmie2;

use function MicroHTML\{A, LABEL, TABLE, TBODY, TD, TEXTAREA, TH, THEAD, TR, joinHTML};

use MicroHTML\HTMLElement;

use function MicroHTML\{INPUT,P,emptyHTML};

/**
 * @phpstan-import-type ArtistArtist from Artists
 * @phpstan-import-type ArtistAlias from Artists
 * @phpstan-import-type ArtistMember from Artists
 * @phpstan-import-type ArtistUrl from Artists
 */
class ArtistsTheme extends Themelet
{
    public function get_author_editor_html(string $author): HTMLElement
    {
        return SHM_POST_INFO(
            "Author",
            $author,
            INPUT(["type" => "text", "name" => "author", "value" => $author])
        );
    }

    public function sidebar_options(string $mode, ?int $artistID = null, bool $is_admin = false): void
    {
        if ($mode === "neutral") {
            $html = SHM_SIMPLE_FORM(
                make_link("artist/new_artist"),
                SHM_SUBMIT("New Artist")
            );
            Ctx::$page->add_block(new Block("Manage Artists", $html, "left", 10));
        }

        if ($mode === "editor") {
            $html = [];
            $html[] = SHM_SIMPLE_FORM(
                make_link("artist/new_artist"),
                SHM_SUBMIT("New Artist")
            );
            $html[] = SHM_SIMPLE_FORM(
                make_link("artist/edit_artist"),
                INPUT(["type" => "hidden", "name" => "artist_id", "value" => $artistID]),
                SHM_SUBMIT("Edit Artist")
            );

            if ($is_admin) {
                $html[] = SHM_SIMPLE_FORM(
                    make_link("artist/nuke_artist"),
                    INPUT(["type" => "hidden", "name" => "artist_id", "value" => $artistID]),
                    SHM_SUBMIT("Delete Artist")
                );
            }

            $html[] = SHM_SIMPLE_FORM(
                make_link("artist/add_alias"),
                INPUT(["type" => "hidden", "name" => "artist_id", "value" => $artistID]),
                SHM_SUBMIT("Add Alias")
            );
            $html[] = SHM_SIMPLE_FORM(
                make_link("artist/add_member"),
                INPUT(["type" => "hidden", "name" => "artist_id", "value" => $artistID]),
                SHM_SUBMIT("Add Member")
            );
            $html[] = SHM_SIMPLE_FORM(
                make_link("artist/add_url"),
                INPUT(["type" => "hidden", "name" => "artist_id", "value" => $artistID]),
                SHM_SUBMIT("Add URL")
            );
            Ctx::$page->add_block(new Block("Manage Artists", joinHTML("", $html), "left", 10));
        }
    }

    /**
     * @param ArtistArtist $artist
     * @param ArtistAlias[] $aliases
     * @param ArtistMember[] $members
     * @param ArtistUrl[] $urls
     */
    public function show_artist_editor(array $artist, array $aliases, array $members, array $urls): void
    {
        $artistName = $artist['name'];
        $artistNotes = $artist['notes'];
        $artistID = $artist['id'];

        // aliases
        $aliasesString = "";
        $aliasesIDsString = "";
        foreach ($aliases as $alias) {
            $aliasesString .= $alias["alias"]." ";
            $aliasesIDsString .= $alias["id"]." ";
        }
        $aliasesString = rtrim($aliasesString);
        $aliasesIDsString = rtrim($aliasesIDsString);

        // members
        $membersString = "";
        $membersIDsString = "";
        foreach ($members as $member) {
            $membersString .= $member["name"]." ";
            $membersIDsString .= $member["id"]." ";
        }
        $membersString = rtrim($membersString);
        $membersIDsString = rtrim($membersIDsString);

        // urls
        $urlsString = "";
        $urlsIDsString = "";
        foreach ($urls as $url) {
            $urlsString .= $url["url"]."\n";
            $urlsIDsString .= $url["id"]." ";
        }
        $urlsString = substr($urlsString, 0, strlen($urlsString) - 1);
        $urlsIDsString = rtrim($urlsIDsString);

        $html = SHM_SIMPLE_FORM(
            make_link("artist/edited/".$artist['id']),
            TABLE(
                ["class" => "form"],
                TR(
                    TH("Name"),
                    TD(
                        INPUT(["type" => "text", "name" => "name", "value" => $artistName]),
                        INPUT(["type" => "hidden", "name" => "id", "value" => $artistID])
                    )
                ),
                TR(
                    TH("Aliases"),
                    TD(
                        INPUT(["type" => "text", "name" => "aliases", "value" => $aliasesString]),
                        INPUT(["type" => "hidden", "name" => "aliasesIDs", "value" => $aliasesIDsString])
                    )
                ),
                TR(
                    TH("Members"),
                    TD(
                        INPUT(["type" => "text", "name" => "members", "value" => $membersString]),
                        INPUT(["type" => "hidden", "name" => "membersIDs", "value" => $membersIDsString])
                    )
                ),
                TR(
                    TH("URLs"),
                    TD(
                        TEXTAREA(["name" => "urls", "value" => $urlsString]),
                        INPUT(["type" => "hidden", "name" => "urlsIDs", "value" => $urlsIDsString])
                    )
                ),
                TR(
                    TH("Notes"),
                    TD(TEXTAREA(["name" => "notes"], $artistNotes))
                ),
                TR(
                    TD(["colspan" => 2], SHM_SUBMIT("Submit"))
                )
            )
        );

        Ctx::$page->add_block(new Block("Edit artist", $html, "main", 10));
    }

    public function new_artist_composer(): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("artist/create"),
            TABLE(
                ["class" => "form"],
                TR(
                    TH("Name"),
                    TD(INPUT(["type" => "text", "name" => "name"]))
                ),
                TR(
                    TH("Aliases"),
                    TD(INPUT(["type" => "text", "name" => "aliases"]))
                ),
                TR(
                    TH("Members"),
                    TD(INPUT(["type" => "text", "name" => "members"]))
                ),
                TR(
                    TH("URLs"),
                    TD(TEXTAREA(["name" => "urls"]))
                ),
                TR(
                    TH("Notes"),
                    TD(TEXTAREA(["name" => "notes"]))
                ),
                TR(
                    TD(["colspan" => 2], SHM_SUBMIT("Submit"))
                )
            )
        );

        Ctx::$page->set_title("Artists");
        Ctx::$page->add_block(new Block("Artists", $html, "main", 10));
    }

    /**
    * @param ArtistArtist[] $artists
    */
    public function list_artists(array $artists, int $pageNumber, int $totalPages): void
    {
        $deletionLinkActionArray = [
            'artist' => 'artist/nuke/',
            'alias' => 'artist/alias/delete/',
            'member' => 'artist/member/delete/',
        ];

        $editionLinkActionArray = [
            'artist' => 'artist/edit/',
            'alias' => 'artist/alias/edit/',
            'member' => 'artist/member/edit/',
        ];

        $typeTextArray = [
            'artist' => 'Artist',
            'alias' => 'Alias',
            'member' => 'Member',
        ];

        $tbody = TBODY();
        foreach ($artists as $artist) {
            if ($artist['type'] !== 'artist') {
                $artist['name'] = str_replace("_", " ", $artist['name']);
            }

            $elementLink = A(["href" => make_link("artist/view/".$artist['artist_id'])], str_replace("_", " ", $artist['name']));
            $user_link = A(["href" => make_link("user/".$artist['user_name'])], $artist['user_name']);
            $edit_link = A(["href" => make_link($editionLinkActionArray[$artist['type']].$artist['id'])], "Edit");
            $del_link = A(["href" => make_link($deletionLinkActionArray[$artist['type']].$artist['id'])], "Delete");

            $tbody->appendChild(TR(
                TD(["class" => "left"], $elementLink),
                TD($typeTextArray[$artist['type']]),
                TD($user_link),
                TD($artist['posts']),
                Ctx::$user->can(ArtistsPermission::EDIT_ARTIST_INFO) ? TD($edit_link) : null,
                Ctx::$user->can(ArtistsPermission::ADMIN) ? TD($del_link) : null,
            ));
        }

        $html = TABLE(
            ["id" => "poolsList", "class" => "zebra"],
            THEAD(
                TR(
                    TH("Name"),
                    TH("Type"),
                    TH("Last updater"),
                    TH("Posts"),
                    Ctx::$user->can(ArtistsPermission::EDIT_ARTIST_INFO) ? TH(["colspan" => "2"], "Action") : null
                )
            ),
            $tbody
        );

        $page = Ctx::$page;
        $page->set_title("Artists");
        $page->add_block(new Block("Artists", $html, "main", 10));
        $this->display_paginator("artist/list", null, $pageNumber, $totalPages);
    }

    public function show_new_alias_composer(int $artistID): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("artist/alias/add"),
            TABLE(
                TR(
                    TH("Alias"),
                    TD(
                        INPUT(["type" => "text", "name" => "aliases"]),
                        INPUT(["type" => "hidden", "name" => "artistID", "value" => $artistID])
                    )
                ),
                TR(
                    TD(["colspan" => 2], SHM_SUBMIT("Submit"))
                )
            )
        );
        Ctx::$page->add_block(new Block("Artist Aliases", $html, "main", 20));
    }

    public function show_new_member_composer(int $artistID): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("artist/member/add"),
            TABLE(
                TR(
                    TH("Members"),
                    TD(
                        INPUT(["type" => "text", "name" => "members"]),
                        INPUT(["type" => "hidden", "name" => "artistID", "value" => $artistID])
                    )
                ),
                TR(
                    TD(["colspan" => 2], SHM_SUBMIT("Submit"))
                )
            )
        );
        Ctx::$page->add_block(new Block("Artist members", $html, "main", 30));
    }

    public function show_new_url_composer(int $artistID): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("artist/url/add"),
            TABLE(
                TR(
                    TH("URLs"),
                    TD(
                        INPUT(["type" => "text", "name" => "urls"]),
                        INPUT(["type" => "hidden", "name" => "artistID", "value" => $artistID])
                    )
                ),
                TR(
                    TD(["colspan" => 2], SHM_SUBMIT("Submit"))
                )
            )
        );
        Ctx::$page->add_block(new Block("Artist URLs", $html, "main", 40));
    }

    /**
     * @param ArtistAlias $alias
     */
    public function show_alias_editor(array $alias): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("artist/alias/edited/".$alias['id']),
            LABEL(["for" => "alias"], "Alias:"),
            INPUT(["type" => "text", "name" => "alias", "id" => "alias", "value" => $alias['alias']]),
            INPUT(["type" => "hidden", "name" => "aliasID", "value" => $alias['id']]),
            SHM_SUBMIT("Submit")
        );
        Ctx::$page->add_block(new Block("Edit Alias", $html, "main", 10));
    }

    /**
     * @param ArtistUrl $url
     */
    public function show_url_editor(array $url): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("artist/url/edited/".$url['id']),
            LABEL(["for" => "url"], "URL:"),
            INPUT(["type" => "text", "name" => "url", "id" => "url", "value" => $url['url']]),
            INPUT(["type" => "hidden", "name" => "urlID", "value" => $url['id']]),
            SHM_SUBMIT("Submit")
        );
        Ctx::$page->add_block(new Block("Edit URL", $html, "main", 10));
    }

    /**
     * @param ArtistMember $member
     */
    public function show_member_editor(array $member): void
    {
        $html = SHM_SIMPLE_FORM(
            make_link("artist/member/edited/".$member['id']),
            LABEL(["for" => "name"], "Member name:"),
            INPUT(["type" => "text", "name" => "name", "id" => "name", "value" => $member['name']]),
            INPUT(["type" => "hidden", "name" => "memberID", "value" => $member['id']]),
            SHM_SUBMIT("Submit")
        );
        Ctx::$page->add_block(new Block("Edit Member", $html, "main", 10));
    }

    /**
     * @param ArtistArtist $artist
     * @param ArtistAlias[] $aliases
     * @param ArtistMember[] $members
     * @param ArtistUrl[] $urls
     * @param Image[] $images
     */
    public function show_artist(array $artist, array $aliases, array $members, array $urls, array $images, bool $userIsLogged, bool $userIsAdmin): void
    {
        $html = TABLE(
            ["id" => "poolsList", "class" => "zebra"],
            TR(TH("Name"), TD(A(["href" => search_link([$artist['name']])], str_replace("_", " ", $artist['name'])))),
            $this->render_aliases($aliases, $userIsLogged, $userIsAdmin),
            $this->render_members($members, $userIsLogged, $userIsAdmin),
            $this->render_urls($urls, $userIsLogged, $userIsAdmin),
            TR(TH("Notes"), TD($artist["notes"])),
        );

        $page = Ctx::$page;
        $page->set_title("Artist");
        $page->add_block(new Block("Artist", $html, "main", 10));

        $images = array_map(fn ($image) => $this->build_thumb($image), $images);
        $page->add_block(new Block("Artist Posts", joinHTML(" ", $images), "main", 20));
    }

    /**
     * @param ArtistAlias[] $aliases
     */
    private function render_aliases(array $aliases, bool $userIsLogged, bool $userIsAdmin): string
    {
        $html = "";
        if (count($aliases) > 0) {
            $aliasViewLink = str_replace("_", " ", $aliases[0]['alias']); // no link anymore
            $aliasEditLink = "<a href='" . make_link("artist/alias/edit/" . $aliases[0]['id']) . "'>Edit</a>";
            $aliasDeleteLink = "<a href='" . make_link("artist/alias/delete/" . $aliases[0]['id']) . "'>Delete</a>";

            $html .= "<tr>
							  <td class='left'>Aliases:</td>
							  <td class='left'>" . $aliasViewLink . "</td>";

            if ($userIsLogged) {
                $html .= "<td class='left'>" . $aliasEditLink . "</td>";
            }

            if ($userIsAdmin) {
                $html .= "<td class='left'>" . $aliasDeleteLink . "</td>";
            }

            $html .= "</tr>";

            if (count($aliases) > 1) {
                $ac = count($aliases);
                for ($i = 1; $i < $ac; $i++) {
                    $aliasViewLink = str_replace("_", " ", $aliases[$i]['alias']); // no link anymore
                    $aliasEditLink = "<a href='" . make_link("artist/alias/edit/" . $aliases[$i]['id']) . "'>Edit</a>";
                    $aliasDeleteLink = "<a href='" . make_link("artist/alias/delete/" . $aliases[$i]['id']) . "'>Delete</a>";

                    $html .= "<tr>
									  <td class='left'></td>
									  <td class='left'>" . $aliasViewLink . "</td>";
                    if ($userIsLogged) {
                        $html .= "<td class='left'>" . $aliasEditLink . "</td>";
                    }
                    if ($userIsAdmin) {
                        $html .= "<td class='left'>" . $aliasDeleteLink . "</td>";
                    }

                    $html .= "</tr>";
                }
            }
        }
        return $html;
    }

    /**
     * @param ArtistMember[] $members
     */
    private function render_members(array $members, bool $userIsLogged, bool $userIsAdmin): string
    {
        $html = "";
        if (count($members) > 0) {
            $memberViewLink = str_replace("_", " ", $members[0]['name']); // no link anymore
            $memberEditLink = "<a href='" . make_link("artist/member/edit/" . $members[0]['id']) . "'>Edit</a>";
            $memberDeleteLink = "<a href='" . make_link("artist/member/delete/" . $members[0]['id']) . "'>Delete</a>";

            $html .= "<tr>
							<td class='left'>Members:</td>
							<td class='left'>" . $memberViewLink . "</td>";
            if ($userIsLogged) {
                $html .= "<td class='left'>" . $memberEditLink . "</td>";
            }
            if ($userIsAdmin) {
                $html .= "<td class='left'>" . $memberDeleteLink . "</td>";
            }

            $html .= "</tr>";

            if (count($members) > 1) {
                $mc = count($members);
                for ($i = 1; $i < $mc; $i++) {
                    $memberViewLink = str_replace("_", " ", $members[$i]['name']); // no link anymore
                    $memberEditLink = "<a href='" . make_link("artist/member/edit/" . $members[$i]['id']) . "'>Edit</a>";
                    $memberDeleteLink = "<a href='" . make_link("artist/member/delete/" . $members[$i]['id']) . "'>Delete</a>";

                    $html .= "<tr>
							<td class='left'></td>
							<td class='left'>" . $memberViewLink . "</td>";
                    if ($userIsLogged) {
                        $html .= "<td class='left'>" . $memberEditLink . "</td>";
                    }
                    if ($userIsAdmin) {
                        $html .= "<td class='left'>" . $memberDeleteLink . "</td>";
                    }

                    $html .= "</tr>";
                }
            }
        }
        return $html;
    }

    /**
     * @param ArtistUrl[] $urls
     */
    private function render_urls(array $urls, bool $userIsLogged, bool $userIsAdmin): string
    {
        $html = "";
        if (count($urls) > 0) {
            $urlViewLink = "<a href='" . str_replace("_", " ", $urls[0]['url']) . "' target='_blank'>" . str_replace("_", " ", $urls[0]['url']) . "</a>";
            $urlEditLink = "<a href='" . make_link("artist/url/edit/" . $urls[0]['id']) . "'>Edit</a>";
            $urlDeleteLink = "<a href='" . make_link("artist/url/delete/" . $urls[0]['id']) . "'>Delete</a>";

            $html .= "<tr>
							<td class='left'>URLs:</td>
							<td class='left'>" . $urlViewLink . "</td>";

            if ($userIsLogged) {
                $html .= "<td class='left'>" . $urlEditLink . "</td>";
            }

            if ($userIsAdmin) {
                $html .= "<td class='left'>" . $urlDeleteLink . "</td>";
            }

            $html .= "</tr>";

            if (count($urls) > 1) {
                $uc = count($urls);
                for ($i = 1; $i < $uc; $i++) {
                    $urlViewLink = "<a href='" . str_replace("_", " ", $urls[$i]['url']) . "' target='_blank'>" . str_replace("_", " ", $urls[$i]['url']) . "</a>";
                    $urlEditLink = "<a href='" . make_link("artist/url/edit/" . $urls[$i]['id']) . "'>Edit</a>";
                    $urlDeleteLink = "<a href='" . make_link("artist/url/delete/" . $urls[$i]['id']) . "'>Delete</a>";

                    $html .= "<tr>
								<td class='left'></td>
								<td class='left'>" . $urlViewLink . "</td>";
                    if ($userIsLogged) {
                        $html .= "<td class='left'>" . $urlEditLink . "</td>";
                    }

                    if ($userIsAdmin) {
                        $html .= "<td class='left'>" . $urlDeleteLink . "</td>";
                    }

                    $html .= "</tr>";
                }
                return $html;
            }
        }
        return $html;
    }

    public function get_help_html(): HTMLElement
    {
        return emptyHTML(
            P("Search for posts with a particular artist."),
            SHM_COMMAND_EXAMPLE("artist=leonardo", "Returns posts with the artist \"leonardo\"")
        );
    }
}
