/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { LinkFieldPresenter } from "./LinkFieldPresenter";
import type { RetrieveAllLinkedArtifacts } from "../../../../domain/fields/link-field-v2/RetrieveAllLinkedArtifacts";
import type { CurrentArtifactIdentifier } from "../../../../domain/CurrentArtifactIdentifier";
import type { Fault } from "@tuleap/fault";
import { isFault } from "@tuleap/fault";

export interface LinkFieldControllerType {
    displayLinkedArtifacts(): Promise<LinkFieldPresenter>;
}

const isCreationModeFault = (fault: Fault): boolean => {
    return typeof fault.isCreationMode === "function" && fault.isCreationMode();
};

export const LinkFieldController = (
    links_retriever: RetrieveAllLinkedArtifacts,
    current_artifact_identifier: CurrentArtifactIdentifier | null
): LinkFieldControllerType => {
    return {
        displayLinkedArtifacts: (): Promise<LinkFieldPresenter> => {
            return links_retriever
                .getLinkedArtifacts(current_artifact_identifier)
                .then((result) => {
                    if (!isFault(result)) {
                        return LinkFieldPresenter.fromArtifacts(result);
                    }
                    if (isCreationModeFault(result)) {
                        return LinkFieldPresenter.forCreationMode();
                    }
                    return LinkFieldPresenter.fromFault(result);
                });
        },
    };
};
