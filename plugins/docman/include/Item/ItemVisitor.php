<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Docman\Item;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;

/**
 * @template ReturnType
 */
interface ItemVisitor
{
    /**
     * @psalm-return ReturnType
     */
    public function visitFolder(Docman_Folder $item, array $params = []);

    /**
     * @psalm-return ReturnType
     */
    public function visitWiki(Docman_Wiki $item, array $params = []);

    /**
     * @psalm-return ReturnType
     */
    public function visitLink(Docman_Link $item, array $params = []);

    /**
     * @psalm-return ReturnType
     */
    public function visitFile(Docman_File $item, array $params = []);

    /**
     * @psalm-return ReturnType
     */
    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = []);

    /**
     * @psalm-return ReturnType
     */
    public function visitEmpty(Docman_Empty $item, array $params = []);

    /**
     * @psalm-return ReturnType
     */
    public function visitItem(Docman_Item $item, array $params = []);

    /**
     * @psalm-return ReturnType
     */
    public function visitOtherDocument(OtherDocument $item, array $params = []);
}
