<?php
/**
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Docman\REST\v1\Folders;

use Docman_FilterGlobalText;
use Docman_FilterText;
use Docman_Report;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Search\CustomPropertyRepresentation;
use Tuleap\Docman\REST\v1\Search\PostSearchRepresentation;
use Tuleap\Docman\REST\v1\Search\SearchDateRepresentation;
use Tuleap\Docman\Search\AlwaysThereColumnRetriever;
use Tuleap\Docman\Search\ColumnReportAugmenter;

class SearchReportBuilder
{
    public function __construct(
        private \Docman_MetadataFactory $metadata_factory,
        private \Docman_FilterFactory $filter_factory,
        private ItemStatusMapper $status_mapper,
        private AlwaysThereColumnRetriever $always_there_column_retriever,
        private ColumnReportAugmenter $column_report_builder,
        private \UserManager $user_manager,
    ) {
    }

    public function buildReport(\Docman_Folder $item, PostSearchRepresentation $search): Docman_Report
    {
        $report = new Docman_Report();
        $report->initFromRow(
            [
                'group_id' => $item->getGroupId(),
                'item_id'  => $item->getId(),
            ]
        );

        $this->addGlobalTextFilter($search, $report);
        $this->addTypeFilter($search, $report);
        $this->addTitleFilter($search, $report);
        $this->addDescriptionFilter($search, $report);
        $this->addOwnerFilter($search, $report);
        $this->addCreateDateFilter($search, $report);
        $this->addUpdateDateFilter($search, $report);
        $this->addObsolescenceDateFilter($search, $report);
        $this->addStatusFilter($search, $report);
        foreach ($search->custom_properties as $custom_property) {
            $this->addCustomPropertyFilter($custom_property, $report);
        }

        $columns = $this->always_there_column_retriever->getColumns();
        $this->column_report_builder->addColumnsFromArray($columns, $report);


        return $report;
    }

    private function addGlobalTextFilter(PostSearchRepresentation $search, Docman_Report $report): void
    {
        $global_search_metadata = $this->filter_factory->getGlobalSearchMetadata();
        $this->getCustomTextFieldsMetadata();
        $filter = new Docman_FilterGlobalText($global_search_metadata, $this->filter_factory->dynTextFields);
        $filter->setValue($search->global_search);
        $report->addFilter($filter);
    }

    private function addTypeFilter(PostSearchRepresentation $search, Docman_Report $report): void
    {
        if ($search->type) {
            $type_filter = new \Docman_FilterItemType($this->filter_factory->getItemTypeSearchMetadata());

            $human_readable_value_to_internal_value = [
                'file'     => PLUGIN_DOCMAN_ITEM_TYPE_FILE,
                'wiki'     => PLUGIN_DOCMAN_ITEM_TYPE_WIKI,
                'embedded' => PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
                'empty'    => PLUGIN_DOCMAN_ITEM_TYPE_EMPTY,
                'link'     => PLUGIN_DOCMAN_ITEM_TYPE_LINK,
                'folder'   => PLUGIN_DOCMAN_ITEM_TYPE_FOLDER,
            ];
            $type_filter->setValue($human_readable_value_to_internal_value[$search->type]);
            $report->addFilter($type_filter);
        }
    }

    private function addTitleFilter(PostSearchRepresentation $search, Docman_Report $report): void
    {
        $metadata = $this->metadata_factory->getHardCodedMetadataFromLabel(
            \Docman_MetadataFactory::HARDCODED_METADATA_TITLE_LABEL
        );

        $this->addTextFilter($report, $search->title, $metadata);
    }

    private function addDescriptionFilter(PostSearchRepresentation $search, Docman_Report $report): void
    {
        $metadata = $this->metadata_factory->getHardCodedMetadataFromLabel(
            \Docman_MetadataFactory::HARDCODED_METADATA_DESCRIPTION_LABEL
        );

        $this->addTextFilter($report, $search->description, $metadata);
    }

    private function addTextFilter(Docman_Report $report, string $search_term, \Docman_Metadata $metadata): void
    {
        if ($search_term) {
            $text_filter = new Docman_FilterText($metadata);
            $text_filter->setValue($search_term);
            $report->addFilter($text_filter);
        }
    }

    private function getCustomTextFieldsMetadata(): void
    {
        $custom_metadata_array = $this->metadata_factory->getRealMetadataList(true);
        foreach ($custom_metadata_array as $custom_metadata) {
            $this->filter_factory->createFromMetadata($custom_metadata, null);
        }
    }

    private function addOwnerFilter(PostSearchRepresentation $search, Docman_Report $report): void
    {
        if ($search->owner) {
            $owner_filter = new \Docman_FilterOwner(
                $this->metadata_factory->getHardCodedMetadataFromLabel(
                    \Docman_MetadataFactory::HARDCODED_METADATA_OWNER_LABEL
                )
            );

            $owner = $this->user_manager->findUser($search->owner);

            $username_to_search = $owner ? $owner->getUserName() : $search->owner;

            $owner_filter->setValue($username_to_search);
            $report->addFilter($owner_filter);
        }
    }

    private function addCreateDateFilter(PostSearchRepresentation $search, Docman_Report $report): void
    {
        if (! $search->create_date) {
            return;
        }

        $metadata = $this->metadata_factory->getHardCodedMetadataFromLabel(
            \Docman_MetadataFactory::HARDCODED_METADATA_CREATE_DATE_LABEL
        );

        $this->addDateFilter($report, $search->create_date, $metadata);
    }

    private function addUpdateDateFilter(PostSearchRepresentation $search, Docman_Report $report): void
    {
        if (! $search->update_date) {
            return;
        }

        $metadata = $this->metadata_factory->getHardCodedMetadataFromLabel(
            \Docman_MetadataFactory::HARDCODED_METADATA_UPDATE_DATE_LABEL
        );

        $this->addDateFilter($report, $search->update_date, $metadata);
    }

    private function addObsolescenceDateFilter(PostSearchRepresentation $search, Docman_Report $report): void
    {
        if (! $search->obsolescence_date) {
            return;
        }

        $metadata = $this->metadata_factory->getHardCodedMetadataFromLabel(
            \Docman_MetadataFactory::HARDCODED_METADATA_OBSOLESCENCE_LABEL
        );
        $this->metadata_factory->appendHardCodedMetadataParams($metadata);

        $this->addDateFilter($report, $search->obsolescence_date, $metadata);
    }

    private function addDateFilter(
        Docman_Report $report,
        SearchDateRepresentation $search_date,
        \Docman_Metadata $metadata,
    ): void {
        $date_filter = new \Docman_FilterDate($metadata);

        $symbol_to_numeric = [
            ">" => 1,
            "=" => 0,
            "<" => -1,
        ];

        $date_filter->setOperator($symbol_to_numeric[$search_date->operator]);
        $date_filter->setValue($search_date->date);

        $report->addFilter($date_filter);
    }

    private function addStatusFilter(PostSearchRepresentation $search, Docman_Report $report): void
    {
        if ($search->status) {
            $list_filter = new \Docman_FilterList(
                $this->metadata_factory->getHardCodedMetadataFromLabel(\Docman_MetadataFactory::HARDCODED_METADATA_STATUS_LABEL)
            );
            $list_filter->setValue($this->status_mapper->getItemStatusIdFromItemStatusString($search->status));
            $report->addFilter($list_filter);
        }
    }

    private function addCustomPropertyFilter(CustomPropertyRepresentation $custom_property, Docman_Report $report): void
    {
        if (! $custom_property->value_date && ! $custom_property->value) {
            return;
        }

        $metadata = $this->metadata_factory->getMetadataFromLabel($custom_property->name);
        if (! $metadata) {
            return;
        }

        if ($custom_property->value_date) {
            $this->addDateFilter($report, $custom_property->value_date, $metadata);

            return;
        }

        if ((int) $metadata->getType() === PLUGIN_DOCMAN_METADATA_TYPE_LIST) {
            $list_filter = new \Docman_FilterList($metadata);
            $list_filter->setValue($custom_property->value);
            $report->addFilter($list_filter);

            return;
        }

        $this->addTextFilter($report, $custom_property->value, $metadata);
    }
}
