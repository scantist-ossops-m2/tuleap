<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

declare(strict_types=1);

namespace Tuleap\DocumentGeneration\Report;

use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_OpenList;
use Tracker_Report;
use Tracker_Report_Criteria;

class ReportCriteriaJsonBuilder
{
    public function buildReportCriteriaJson(Tracker_Report $report): ReportCriteriaJson
    {
        if ($report->is_in_expert_mode) {
            return $this->buildExpertReportCriteriaJson($report);
        }

        return $this->buildClassicReportCriteriaJson($report);
    }

    private function buildExpertReportCriteriaJson(Tracker_Report $report): ExpertReportCriteriaJson
    {
        return new ExpertReportCriteriaJson(
            $report->expert_query
        );
    }

    private function buildClassicReportCriteriaJson(Tracker_Report $report): ClassicReportCriteriaJson
    {
        $criteria_value_json = [];
        foreach ($report->getCriteria() as $criterion) {
            $criterion_field = $criterion->getField();
            if (
                $criterion_field instanceof Tracker_FormElement_Field_OpenList ||
                $criterion_field instanceof Tracker_FormElement_Field_Date
            ) {
                continue;
            }

            if ($criterion_field instanceof Tracker_FormElement_Field_List) {
                $list_criterion_json_value = $this->buildCriterionValueJsonFromListValue($criterion);
                if ($list_criterion_json_value !== null) {
                    $criteria_value_json[] = $list_criterion_json_value;
                }
            } elseif (! empty($criterion_field->getCriteriaValue($criterion))) {
                $criteria_value_json[] = new CriterionValueJson(
                    $criterion_field->getLabel(),
                    (string) $criterion_field->getCriteriaValue($criterion),
                );
            }
        }

        foreach ($report->getAdditionalCriteria() as $additional_criterion) {
            if ($additional_criterion->getValue() !== '') {
                $criteria_value_json[] = new CriterionValueJson(
                    $additional_criterion->getKey(),
                    (string) $additional_criterion->getValue(),
                );
            }
        }

        return new ClassicReportCriteriaJson(
            $criteria_value_json
        );
    }

    private function buildCriterionValueJsonFromListValue(Tracker_Report_Criteria $criterion): ?CriterionValueJson
    {
        $criterion_field = $criterion->getField();
        $criterion_value = $criterion_field->getCriteriaValue($criterion);

        if (! is_array($criterion_value)) {
            return null;
        }

        $criterion_values_labels = [];
        foreach ($criterion_value as $value_id) {
            $value = $criterion_field->getBind()->getValue($value_id);
            if ($value) {
                $criterion_values_labels[] = $value->getLabel();
            }
        }

        if (count($criterion_values_labels) > 0) {
            return new CriterionValueJson(
                $criterion_field->getLabel(),
                implode(', ', $criterion_values_labels),
            );
        }

        return null;
    }
}
