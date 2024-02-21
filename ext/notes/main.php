<?php

declare(strict_types=1);

namespace Shimmie2;

class Notes extends Extension
{
    /** @var NotesTheme */
    protected Themelet $theme;

    public function onInitExt(InitExtEvent $event): void
    {
        Image::$prop_types["notes"] = ImagePropType::INT;
    }

    public function onDatabaseUpgrade(DatabaseUpgradeEvent $event): void
    {
        global $config, $database;

        // shortcut to latest
        if ($this->get_version("ext_notes_version") < 1) {
            $database->execute("ALTER TABLE images ADD COLUMN notes INTEGER NOT NULL DEFAULT 0");
            $database->create_table("notes", "
					id SCORE_AIPK,
					enable INTEGER NOT NULL,
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					user_ip CHAR(15) NOT NULL,
					date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					x1 INTEGER NOT NULL,
					y1 INTEGER NOT NULL,
					height INTEGER NOT NULL,
					width INTEGER NOT NULL,
					note TEXT NOT NULL,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
					");
            $database->execute("CREATE INDEX notes_image_id_idx ON notes(image_id)", []);

            $database->create_table("note_request", "
					id SCORE_AIPK,
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
					");
            $database->execute("CREATE INDEX note_request_image_id_idx ON note_request(image_id)", []);

            $database->create_table("note_histories", "
					id SCORE_AIPK,
					note_enable INTEGER NOT NULL,
					note_id INTEGER NOT NULL,
					review_id INTEGER NOT NULL,
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					user_ip CHAR(15) NOT NULL,
					date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
					x1 INTEGER NOT NULL,
					y1 INTEGER NOT NULL,
					height INTEGER NOT NULL,
					width INTEGER NOT NULL,
					note TEXT NOT NULL,
					FOREIGN KEY (user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
					FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE
					");
            $database->execute("CREATE INDEX note_histories_image_id_idx ON note_histories(image_id)", []);

            $config->set_int("notesNotesPerPage", 20);
            $config->set_int("notesRequestsPerPage", 20);
            $config->set_int("notesHistoriesPerPage", 20);

            $this->set_version("ext_notes_version", 1);
        }
    }

    public function onPageRequest(PageRequestEvent $event): void
    {
        global $page, $user;
        if ($event->page_matches("note/list", paged: true)) {
            $this->get_notes_list($event->get_iarg('page_num', 1) - 1); // This should show images like post/list but i don't know how do that.
        }
        if ($event->page_matches("note/requests", paged: true)) {
            $this->get_notes_requests($event->get_iarg('page_num', 1) - 1); // This should show images like post/list but i don't know how do that.
        }
        if ($event->page_matches("note/updated", paged: true)) {
            $this->get_histories($event->get_iarg('page_num', 1) - 1);
        }
        if ($event->page_matches("note/history/{note_id}", paged: true)) {
            $this->get_history($event->get_iarg('note_id'), $event->get_iarg('page_num', 1) - 1);
        }
        if ($event->page_matches("note/revert/{noteID}/{reviewID}", permission: Permissions::NOTES_EDIT)) {
            $noteID = $event->get_iarg('noteID');
            $reviewID = $event->get_iarg('reviewID');
            $this->revert_history($noteID, $reviewID);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("note/updated"));
        }
        if ($event->page_matches("note/add_request", permission: Permissions::NOTES_REQUEST)) {
            $image_id = int_escape($event->req_POST("image_id"));
            $this->add_note_request($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image_id"));
        }
        if ($event->page_matches("note/nuke_requests", permission: Permissions::NOTES_ADMIN)) {
            $image_id = int_escape($event->req_POST("image_id"));
            $this->nuke_requests($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image_id"));
        }
        if ($event->page_matches("note/create_note", permission: Permissions::NOTES_CREATE)) {
            $page->set_mode(PageMode::DATA);
            $note_id = $this->add_new_note();
            $page->set_data(\Safe\json_encode([
                'status' => 'success',
                'note_id' => $note_id,
            ]));
        }
        if ($event->page_matches("note/update_note", permission: Permissions::NOTES_EDIT)) {
            $page->set_mode(PageMode::DATA);
            $this->update_note();
            $page->set_data(\Safe\json_encode(['status' => 'success']));
        }
        if ($event->page_matches("note/delete_note", permission: Permissions::NOTES_ADMIN)) {
            $page->set_mode(PageMode::DATA);
            $this->delete_note();
            $page->set_data(\Safe\json_encode(['status' => 'success']));
        }
        if ($event->page_matches("note/nuke_notes", permission: Permissions::NOTES_ADMIN)) {
            $image_id = int_escape($event->req_POST("image_id"));
            $this->nuke_notes($image_id);
            $page->set_mode(PageMode::REDIRECT);
            $page->set_redirect(make_link("post/view/$image_id"));
        }
    }


    /*
     * HERE WE LOAD THE NOTES IN THE IMAGE
     */
    public function onDisplayingImage(DisplayingImageEvent $event): void
    {
        global $page, $user;

        //display form on image event
        $notes = $this->get_notes($event->image->id);
        $this->theme->display_note_system($page, $event->image->id, $notes, $user->can(Permissions::NOTES_ADMIN), $user->can(Permissions::NOTES_EDIT));
    }


    /*
     * HERE WE ADD THE BUTTONS ON SIDEBAR
     */
    public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event): void
    {
        global $user;
        if ($user->can(Permissions::NOTES_CREATE)) {
            $event->add_part($this->theme->note_button($event->image->id));

            if ($user->can(Permissions::NOTES_ADMIN)) {
                $event->add_part($this->theme->nuke_notes_button($event->image->id));
                $event->add_part($this->theme->nuke_requests_button($event->image->id));
            }
        }
        if ($user->can(Permissions::NOTES_REQUEST)) {
            $event->add_part($this->theme->request_button($event->image->id));
        }
    }


    /*
     * HERE WE ADD QUERYLETS TO ADD SEARCH SYSTEM
     */
    public function onSearchTermParse(SearchTermParseEvent $event): void
    {
        if (is_null($event->term)) {
            return;
        }

        $matches = [];
        if (preg_match("/^note[=|:](.*)$/i", $event->term, $matches)) {
            $notes = int_escape($matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM notes WHERE note = $notes)"));
        } elseif (preg_match("/^notes([:]?<|[:]?>|[:]?<=|[:]?>=|[:|=])(\d+)%/i", $event->term, $matches)) {
            $cmp = ltrim($matches[1], ":") ?: "=";
            $notes = $matches[2];
            $event->add_querylet(new Querylet("images.id IN (SELECT id FROM images WHERE notes $cmp $notes)"));
        } elseif (preg_match("/^notes_by[=|:](.*)$/i", $event->term, $matches)) {
            $user_id = User::name_to_id($matches[1]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM notes WHERE user_id = $user_id)"));
        } elseif (preg_match("/^(notes_by_userno|notes_by_user_id)[=|:](\d+)$/i", $event->term, $matches)) {
            $user_id = int_escape($matches[2]);
            $event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM notes WHERE user_id = $user_id)"));
        }
    }

    public function onHelpPageBuilding(HelpPageBuildingEvent $event): void
    {
        if ($event->key === HelpPages::SEARCH) {
            $block = new Block();
            $block->header = "Notes";
            $block->body = $this->theme->get_help_html();
            $event->add_block($block);
        }
    }


    /**
     * HERE WE GET ALL NOTES FOR DISPLAYED IMAGE.
     *
     * @return array<string, mixed>
     */
    private function get_notes(int $imageID): array
    {
        global $database;

        return $database->get_all("
            SELECT *
            FROM notes
            WHERE enable = :enable AND image_id = :image_id
            ORDER BY date ASC
        ", ['enable' => '1', 'image_id' => $imageID]);
    }


    /*
     * HERE WE ADD A NOTE TO DATABASE
     */
    private function add_new_note(): int
    {
        global $database, $user;

        $note = json_decode(\Safe\file_get_contents('php://input'), true);

        $database->execute(
            "
				INSERT INTO notes (enable, image_id, user_id, user_ip, date, x1, y1, height, width, note)
				VALUES (:enable, :image_id, :user_id, :user_ip, now(), :x1, :y1, :height, :width, :note)",
            [
                'enable' => 1,
                'image_id' => $note['image_id'],
                'user_id' => $user->id,
                'user_ip' => get_real_ip(),
                'x1' => $note['x1'],
                'y1' => $note['y1'],
                'height' => $note['height'],
                'width' => $note['width'],
                'note' => $note['note'],
            ]
        );

        $noteID = $database->get_last_insert_id('notes_id_seq');

        log_info("notes", "Note added {$noteID} by {$user->name}");

        $database->execute("UPDATE images SET notes=(SELECT COUNT(*) FROM notes WHERE image_id=:id) WHERE id=:id", ['id' => $note['image_id']]);

        $this->add_history(
            1,
            $noteID,
            $note['image_id'],
            $note['x1'],
            $note['y1'],
            $note['height'],
            $note['width'],
            $note['note']
        );

        return $noteID;
    }

    private function add_note_request(int $image_id): void
    {
        global $database, $user;

        $user_id = $user->id;

        $database->execute(
            "
				INSERT INTO note_request (image_id, user_id, date)
				VALUES (:image_id, :user_id, now())",
            ['image_id' => $image_id, 'user_id' => $user_id]
        );

        $resultID = $database->get_last_insert_id('note_request_id_seq');

        log_info("notes", "Note requested {$resultID} by {$user->name}");
    }

    private function update_note(): void
    {
        global $database;

        $note = json_decode(\Safe\file_get_contents('php://input'), true);

        // validate parameters
        if (empty($note['note'])) {
            return;
        }

        $database->execute("
			UPDATE notes
			SET x1 = :x1, y1 = :y1, height = :height, width = :width, note = :note
			WHERE image_id = :image_id AND id = :note_id", $note);

        $this->add_history(1, $note['note_id'], $note['image_id'], $note['x1'], $note['y1'], $note['height'], $note['width'], $note['note']);
    }

    private function delete_note(): void
    {
        global $user, $database;

        $note = json_decode(\Safe\file_get_contents('php://input'), true);
        $database->execute("
			UPDATE notes SET enable = :enable
			WHERE image_id = :image_id AND id = :id
		", ['enable' => 0, 'image_id' => $note["image_id"], 'id' => $note["note_id"]]);

        log_info("notes", "Note deleted {$note["note_id"]} by {$user->name}");
    }

    private function nuke_notes(int $image_id): void
    {
        global $database, $user;
        $database->execute("DELETE FROM notes WHERE image_id = :image_id", ['image_id' => $image_id]);
        log_info("notes", "Notes deleted from {$image_id} by {$user->name}");
    }

    private function nuke_requests(int $image_id): void
    {
        global $database, $user;

        $database->execute("DELETE FROM note_request WHERE image_id = :image_id", ['image_id' => $image_id]);

        log_info("notes", "Requests deleted from {$image_id} by {$user->name}");
    }

    private function get_notes_list(int $pageNumber): void
    {
        global $database, $config;

        $notesPerPage = $config->get_int('notesNotesPerPage');
        $totalPages = (int) ceil($database->get_one("SELECT COUNT(DISTINCT image_id) FROM notes") / $notesPerPage);

        //$result = $database->get_all("SELECT * FROM pool_images WHERE pool_id=:pool_id", ['pool_id'=>$poolID]);
        $image_ids = $database->get_col(
            "
			SELECT DISTINCT image_id
			FROM notes
			WHERE enable = :enable
			ORDER BY date DESC LIMIT :limit OFFSET :offset",
            ['enable' => 1, 'offset' => $pageNumber * $notesPerPage, 'limit' => $notesPerPage]
        );

        $images = [];
        foreach ($image_ids as $id) {
            $images[] = Image::by_id_ex($id);
        }

        $this->theme->display_note_list($images, $pageNumber + 1, $totalPages);
    }

    private function get_notes_requests(int $pageNumber): void
    {
        global $config, $database;

        $requestsPerPage = $config->get_int('notesRequestsPerPage');

        //$result = $database->get_all("SELECT * FROM pool_images WHERE pool_id=:pool_id", ['pool_id'=>$poolID]);

        $result = $database->execute(
            "
				SELECT DISTINCT image_id
				FROM note_request
				ORDER BY date DESC LIMIT :limit OFFSET :offset",
            ["offset" => $pageNumber * $requestsPerPage, "limit" => $requestsPerPage]
        );

        $totalPages = (int) ceil($database->get_one("SELECT COUNT(*) FROM note_request") / $requestsPerPage);

        $images = [];
        while ($row = $result->fetch()) {
            $images[] = Image::by_id_ex($row["image_id"]);
        }

        $this->theme->display_note_requests($images, $pageNumber + 1, $totalPages);
    }

    private function add_history(int $noteEnable, int $noteID, int $imageID, int $noteX1, int $noteY1, int $noteHeight, int $noteWidth, string $noteText): void
    {
        global $user, $database;

        $reviewID = $database->get_one("SELECT COUNT(*) FROM note_histories WHERE note_id = :note_id", ['note_id' => $noteID]);
        $reviewID = $reviewID + 1;

        $database->execute(
            "
				INSERT INTO note_histories (note_enable, note_id, review_id, image_id, user_id, user_ip, date, x1, y1, height, width, note)
				VALUES (:note_enable, :note_id, :review_id, :image_id, :user_id, :user_ip, now(), :x1, :y1, :height, :width, :note)
			",
            [
                'note_enable' => $noteEnable,
                'note_id' => $noteID,
                'review_id' => $reviewID,
                'image_id' => $imageID,
                'user_id' => $user->id,
                'user_ip' => get_real_ip(),
                'x1' => $noteX1,
                'y1' => $noteY1,
                'height' => $noteHeight,
                'width' => $noteWidth,
                'note' => $noteText
            ]
        );
    }

    private function get_histories(int $pageNumber): void
    {
        global $config, $database;

        $historiesPerPage = $config->get_int('notesHistoriesPerPage');

        //ORDER BY IMAGE & DATE
        $histories = $database->get_all(
            "SELECT h.note_id, h.review_id, h.image_id, h.date, h.note, u.name AS user_name " .
            "FROM note_histories AS h " .
            "INNER JOIN users AS u " .
            "ON u.id = h.user_id " .
            "ORDER BY date DESC LIMIT :limit OFFSET :offset",
            ['offset' => $pageNumber * $historiesPerPage, 'limit' => $historiesPerPage]
        );

        $totalPages = (int) ceil($database->get_one("SELECT COUNT(*) FROM note_histories") / $historiesPerPage);

        $this->theme->display_histories($histories, $pageNumber + 1, $totalPages);
    }

    private function get_history(int $noteID, int $pageNumber): void
    {
        global $config, $database;

        $historiesPerPage = $config->get_int('notesHistoriesPerPage');

        $histories = $database->get_all(
            "SELECT h.note_id, h.review_id, h.image_id, h.date, h.note, u.name AS user_name " .
            "FROM note_histories AS h " .
            "INNER JOIN users AS u " .
            "ON u.id = h.user_id " .
            "WHERE note_id = :note_id " .
            "ORDER BY date DESC LIMIT :limit OFFSET :offset",
            ['note_id' => $noteID, 'offset' => $pageNumber * $historiesPerPage, 'limit' => $historiesPerPage]
        );

        $totalPages = (int) ceil($database->get_one("SELECT COUNT(*) FROM note_histories WHERE note_id = :note_id", ['note_id' => $noteID]) / $historiesPerPage);

        $this->theme->display_history($histories, $pageNumber + 1, $totalPages);
    }

    /**
     * HERE GO BACK IN HISTORY AND SET THE OLD NOTE. IF WAS REMOVED WE RE-ADD IT.
     */
    private function revert_history(int $noteID, int $reviewID): void
    {
        global $database;

        $history = $database->get_row("SELECT * FROM note_histories WHERE note_id = :note_id AND review_id = :review_id", ['note_id' => $noteID, 'review_id' => $reviewID]);

        $noteEnable = $history['note_enable'];
        $noteID = $history['note_id'];
        $imageID = $history['image_id'];
        $noteX1 = $history['x1'];
        $noteY1 = $history['y1'];
        $noteHeight = $history['height'];
        $noteWidth = $history['width'];
        $noteText = $history['note'];

        $database->execute("
			UPDATE notes
			SET enable = :enable, x1 = :x1, y1 = :y1, height = :height, width = :width, note = :note
			WHERE image_id = :image_id AND id = :id
		", ['enable' => 1, 'x1' => $noteX1, 'y1' => $noteY1, 'height' => $noteHeight, 'width' => $noteWidth, 'note' => $noteText, 'image_id' => $imageID, 'id' => $noteID]);

        $this->add_history($noteEnable, $noteID, $imageID, $noteX1, $noteY1, $noteHeight, $noteWidth, $noteText);
    }
}
