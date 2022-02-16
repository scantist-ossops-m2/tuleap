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

use Docman_FilterFactory;
use Docman_Metadata;
use Docman_MetadataFactory;
use Docman_ReportColumnTitle;
use Docman_SettingsBo;
use Tuleap\Docman\REST\v1\Metadata\ItemStatusMapper;
use Tuleap\Docman\REST\v1\Search\CustomPropertyRepresentation;
use Tuleap\Docman\REST\v1\Search\PostSearchRepresentation;
use Tuleap\Docman\REST\v1\Search\SearchDateRepresentation;
use Tuleap\Docman\Search\AlwaysThereColumnRetriever;
use Tuleap\Docman\Search\ColumnReportAugmenter;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class SearchReportBuilderTest extends TestCase
{
    private SearchReportBuilder $search_report_builder;
    private Docman_MetadataFactory|\PHPUnit\Framework\MockObject\MockObject $metadata_factory;

    protected function setUp(): void
    {
        $this->metadata_factory = $this->getMockBuilder(Docman_MetadataFactory::class)
            ->setConstructorArgs([101])
            ->onlyMethods(['getRealMetadataList', 'getMetadataFromLabel'])
            ->getMock();
        $this->metadata_factory->method("getRealMetadataList")->willReturn([]);
        $filter_factory  = new Docman_FilterFactory(101);
        $docman_settings = $this->createMock(Docman_SettingsBo::class);
        $docman_settings->method('getMetadataUsage')->willReturn("1");
        $always_there_column_retriever = new AlwaysThereColumnRetriever($docman_settings);

        $column_factory = $this->createMock(\Docman_ReportColumnFactory::class);
        $metadata       = new Docman_Metadata();
        $metadata->setLabel("My column");
        $column_title = new Docman_ReportColumnTitle($metadata);
        $column_factory->method("getColumnFromLabel")->willReturn($column_title);

        $column_report_builder = new ColumnReportAugmenter($column_factory);

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager
            ->method('findUser')
            ->with('John Doe (jdoe)')
            ->willReturn(UserTestBuilder::aUser()->withUserName('jdoe')->build());

        $this->search_report_builder = new SearchReportBuilder(
            $this->metadata_factory,
            $filter_factory,
            new ItemStatusMapper($docman_settings),
            $always_there_column_retriever,
            $column_report_builder,
            $user_manager,
        );
    }

    public function testItBuildsAReportWithAGlobalSearchFilter(): void
    {
        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";

        $report = $this->search_report_builder->buildReport($folder, $search);
        self::assertSame($search->global_search, $report->getFiltersArray()[0]->value);
        self::assertSame("My column", $report->columns[0]->md->getLabel());
    }

    /**
     * @testWith ["folder", 1]
     *           ["file", 2]
     *           ["link", 3]
     *           ["embedded", 4]
     *           ["wiki", 5]
     *           ["empty", 6]
     */
    public function testItBuildsAReportWithATypeSearchFilter(string $submitted_type_value, int $expected_internal_value): void
    {
        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";
        $search->type          = $submitted_type_value;

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame("item_type", $second_filter->md->getLabel());
        self::assertSame($expected_internal_value, $second_filter->value);
    }

    public function testItBuildsAReportWithATitleSearchFilter(): void
    {
        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";
        $search->title         = "lorem";

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame("title", $second_filter->md->getLabel());
        self::assertSame("lorem", $second_filter->value);
    }

    /**
     * @testWith ["none", 100]
     *           ["draft", 101]
     *           ["approved", 102]
     *           ["rejected", 103]
     */
    public function testItBuildsAReportWithAStatusSearchFilter(string $submitted_status_value, int $expected_internal_value): void
    {
        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";
        $search->status        = $submitted_status_value;

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame("status", $second_filter->md->getLabel());
        self::assertSame($expected_internal_value, $second_filter->value);
    }

    public function testItBuildsAReportWithADescriptionSearchFilter(): void
    {
        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";
        $search->description   = "lorem";

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame("description", $second_filter->md->getLabel());
        self::assertSame("lorem", $second_filter->value);
    }

    public function testItBuildsAReportWithAOwnerSearchFilterAndUseTheUsernameToSearch(): void
    {
        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";
        $search->owner         = "John Doe (jdoe)";

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame("owner", $second_filter->md->getLabel());
        self::assertSame("jdoe", $second_filter->value);
    }

    /**
     * @testWith [">", 1]
     *           ["=", 0]
     *           ["<", -1]
     */
    public function testItBuildsAReportWithAnUpdateDateSearchFilter(string $symbol_operator, int $expected_numeric_operator): void
    {
        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";
        $search->update_date   = new SearchDateRepresentation();

        $search->update_date->operator = $symbol_operator;
        $search->update_date->date     = "2022-01-30";

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        assert($second_filter instanceof \Docman_FilterDate);
        self::assertSame("update_date", $second_filter->md->getLabel());
        self::assertSame("2022-01-30", $second_filter->value);
        self::assertSame($expected_numeric_operator, $second_filter->operator);
    }

    /**
     * @testWith [">", 1]
     *           ["=", 0]
     *           ["<", -1]
     */
    public function testItBuildsAReportWithACreateDateSearchFilter(string $symbol_operator, int $expected_numeric_operator): void
    {
        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";
        $search->create_date   = new SearchDateRepresentation();

        $search->create_date->operator = $symbol_operator;
        $search->create_date->date     = "2022-01-30";

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        assert($second_filter instanceof \Docman_FilterDate);
        self::assertSame("create_date", $second_filter->md->getLabel());
        self::assertSame("2022-01-30", $second_filter->value);
        self::assertSame($expected_numeric_operator, $second_filter->operator);
    }

    public function testItBuildsAReportWithACustomTextSearchFilter(): void
    {
        $metadata = new \Docman_Metadata();
        $metadata->setLabel('field_2');
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_TEXT);

        $this->metadata_factory
            ->method('getMetadataFromLabel')
            ->with('field_2')
            ->willReturn($metadata);

        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";

        $property        = new CustomPropertyRepresentation();
        $property->name  = 'field_2';
        $property->value = "lorem";

        $search->custom_properties = [$property];

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame("field_2", $second_filter->md->getLabel());
        self::assertSame("lorem", $second_filter->value);
    }

    public function testItBuildsAReportWithACustomStringSearchFilter(): void
    {
        $metadata = new \Docman_Metadata();
        $metadata->setLabel('field_2');
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_STRING);

        $this->metadata_factory
            ->method('getMetadataFromLabel')
            ->with('field_2')
            ->willReturn($metadata);

        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";

        $property        = new CustomPropertyRepresentation();
        $property->name  = 'field_2';
        $property->value = "lorem";

        $search->custom_properties = [$property];

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertSame("field_2", $second_filter->md->getLabel());
        self::assertSame("lorem", $second_filter->value);
    }

    public function testItBuildsAReportWithACustomListSearchFilter(): void
    {
        $metadata = new \Docman_Metadata();
        $metadata->setLabel('field_2');
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_LIST);

        $this->metadata_factory
            ->method('getMetadataFromLabel')
            ->with('field_2')
            ->willReturn($metadata);

        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";

        $property        = new CustomPropertyRepresentation();
        $property->name  = 'field_2';
        $property->value = "lorem";

        $search->custom_properties = [$property];

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        self::assertInstanceOf(\Docman_FilterList::class, $second_filter);
        self::assertSame("field_2", $second_filter->md->getLabel());
        self::assertSame("lorem", $second_filter->value);
    }

    /**
     * @testWith [">", 1]
     *           ["=", 0]
     *           ["<", -1]
     */
    public function testItBuildsAReportWithACustomDateSearchFilter(string $symbol_operator, int $expected_numeric_operator): void
    {
        $metadata = new \Docman_Metadata();
        $metadata->setLabel('field_2');
        $metadata->setType(PLUGIN_DOCMAN_METADATA_TYPE_DATE);

        $this->metadata_factory
            ->method('getMetadataFromLabel')
            ->with('field_2')
            ->willReturn($metadata);

        $folder                = new \Docman_Folder(['item_id' => 1, 'group_id' => 101]);
        $search                = new PostSearchRepresentation();
        $search->global_search = "*.docx";

        $property             = new CustomPropertyRepresentation();
        $property->name       = 'field_2';
        $property->value_date = new SearchDateRepresentation();

        $property->value_date->operator = $symbol_operator;
        $property->value_date->date     = "2022-01-30";

        $search->custom_properties = [$property];

        $report       = $this->search_report_builder->buildReport($folder, $search);
        $first_filter = $report->getFiltersArray()[0];
        self::assertSame("global_txt", $first_filter->md->getLabel());
        self::assertSame("*.docx", $first_filter->value);
        $second_filter = $report->getFiltersArray()[1];
        assert($second_filter instanceof \Docman_FilterDate);
        self::assertSame("field_2", $second_filter->md->getLabel());
        self::assertSame("2022-01-30", $second_filter->value);
        self::assertSame($expected_numeric_operator, $second_filter->operator);
    }
}
