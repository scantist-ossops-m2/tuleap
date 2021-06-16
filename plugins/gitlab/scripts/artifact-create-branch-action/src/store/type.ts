/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

export interface State {
    integrations: Array<GitlabIntegration>;
    artifact_id: number;
    branch_name: string;
}

export interface GitlabIntegration {
    description: string;
    gitlab_repository_url: string;
    gitlab_repository_id: number;
    id: number;
    last_push_date: string;
    name: string;
    is_webhook_configured: boolean;
    allow_artifact_closure: boolean;
}
