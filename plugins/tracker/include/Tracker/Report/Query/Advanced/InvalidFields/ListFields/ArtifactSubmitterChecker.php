<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use Tuleap\Tracker\Report\Query\Advanced\CollectionOfListValuesExtractor;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\ComparisonType;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\FieldIsNotSupportedForComparisonException;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;

final readonly class ArtifactSubmitterChecker implements InvalidFieldChecker
{
    public function __construct(
        private \UserManager $user_manager,
    ) {
    }

    /**
     * @throws FieldIsNotSupportedForComparisonException
     * @throws ListToEmptyStringTermException
     * @throws ListToMySelfForAnonymousComparisonException
     * @throws ListToNowComparisonException
     * @throws ListToStatusOpenComparisonException
     * @throws SubmittedByUserDoesntExistException
     */
    public function checkFieldIsValidForComparison(Comparison $comparison, \Tracker_FormElement_Field $field): void
    {
        match ($comparison->getType()) {
            ComparisonType::Equal,
            ComparisonType::NotEqual => $this->checkValueIsValid($comparison, $field, false),
            ComparisonType::In,
            ComparisonType::NotIn    => $this->checkValueIsValid($comparison, $field, true),
            ComparisonType::Between => throw new FieldIsNotSupportedForComparisonException($field, 'between()'),
            ComparisonType::GreaterThan => throw new FieldIsNotSupportedForComparisonException($field, '>'),
            ComparisonType::GreaterThanOrEqual => throw new FieldIsNotSupportedForComparisonException($field, '>='),
            ComparisonType::LesserThan => throw new FieldIsNotSupportedForComparisonException($field, '<'),
            ComparisonType::LesserThanOrEqual => throw new FieldIsNotSupportedForComparisonException($field, '<='),
        };
    }

    /**
     * @throws ListToEmptyStringTermException
     * @throws ListToMySelfForAnonymousComparisonException
     * @throws ListToNowComparisonException
     * @throws ListToStatusOpenComparisonException
     * @throws SubmittedByUserDoesntExistException
     */
    private function checkValueIsValid(
        Comparison $comparison,
        \Tracker_FormElement_Field $field,
        bool $is_empty_string_a_problem,
    ): void {
        $extractor = new CollectionOfListValuesExtractor();
        $values    = $extractor->extractCollectionOfValues($comparison->getValueWrapper(), $field);

        foreach ($values as $value) {
            if ($value === '') {
                if ($is_empty_string_a_problem) {
                    throw new ListToEmptyStringTermException($comparison, $field);
                }
                continue;
            }

            $user = $this->user_manager->getUserByLoginName((string) $value);

            if (! $user) {
                throw new SubmittedByUserDoesntExistException($field, (string) $value);
            }
        }
    }
}
