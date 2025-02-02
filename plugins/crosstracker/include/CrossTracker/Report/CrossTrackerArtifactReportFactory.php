<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report;

use PFUser;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerArtifactReportDao;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidSearchablesCollectionBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\InvalidTermCollectorVisitor;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\CrossTrackerExpertQueryReportDao;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilderVisitor;
use Tuleap\Tracker\Artifact\RetrieveArtifact;
use Tuleap\Tracker\Report\Query\Advanced\ExpertQueryValidator;
use Tuleap\Tracker\Report\Query\Advanced\ParserCacheProxy;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\REST\v1\ArtifactMatchingReportCollection;

final readonly class CrossTrackerArtifactReportFactory
{
    public function __construct(
        private CrossTrackerArtifactReportDao $artifact_report_dao,
        private RetrieveArtifact $artifact_factory,
        private ExpertQueryValidator $expert_query_validator,
        private QueryBuilderVisitor $query_builder,
        private ParserCacheProxy $parser,
        private CrossTrackerExpertQueryReportDao $expert_query_dao,
        private InvalidTermCollectorVisitor $collector,
    ) {
    }

    /**
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     */
    public function getArtifactsMatchingReport(
        CrossTrackerReport $report,
        PFUser $current_user,
        int $limit,
        int $offset,
    ): ArtifactMatchingReportCollection {
        if ($report->getExpertQuery() === '') {
            return $this->getArtifactsFromGivenTrackers(
                $report->getTrackers(),
                $limit,
                $offset
            );
        } else {
            return $this->getArtifactsMatchingExpertQuery(
                $report,
                $current_user,
                $limit,
                $offset
            );
        }
    }

    /**
     * @param Tracker[] $trackers
     */
    private function getArtifactsFromGivenTrackers(array $trackers, int $limit, int $offset): ArtifactMatchingReportCollection
    {
        if (count($trackers) === 0) {
            return new ArtifactMatchingReportCollection([], 0);
        }

        $trackers_id = $this->getTrackersId($trackers);

        $result     = $this->artifact_report_dao->searchArtifactsFromTracker($trackers_id, $limit, $offset);
        $total_size = $this->artifact_report_dao->foundRows();
        return $this->buildCollectionOfArtifacts($result, $total_size);
    }

    /**
     * @throws SearchablesAreInvalidException
     * @throws SearchablesDoNotExistException
     */
    private function getArtifactsMatchingExpertQuery(
        CrossTrackerReport $report,
        PFUser $current_user,
        int $limit,
        int $offset,
    ): ArtifactMatchingReportCollection {
        $trackers     = $this->getOnlyActiveTrackersInActiveProjects($report->getTrackers());
        $expert_query = $report->getExpertQuery();
        $this->expert_query_validator->validateExpertQuery(
            $expert_query,
            new InvalidSearchablesCollectionBuilder($this->collector, $trackers, $current_user)
        );
        $parsed_expert_query   = $this->parser->parse($expert_query);
        $additional_from_where = $this->query_builder->buildFromWhere($parsed_expert_query, $trackers, $current_user);
        $results               = $this->expert_query_dao->searchArtifactsMatchingQuery(
            $additional_from_where,
            $this->getTrackersId($trackers),
            $limit,
            $offset
        );
        $total_size            = $this->expert_query_dao->foundRows();
        return $this->buildCollectionOfArtifacts($results, $total_size);
    }

    /**
     * @param Tracker[] $trackers
     * @return Tracker[]
     */
    private function getOnlyActiveTrackersInActiveProjects(array $trackers): array
    {
        return array_filter($trackers, static fn(Tracker $tracker) => $tracker->isActive() && $tracker->getProject()->isActive());
    }

    /**
     * @param Tracker[] $trackers
     * @return int[]
     */
    private function getTrackersId(array $trackers): array
    {
        $id = [];

        foreach ($trackers as $tracker) {
            $id[] = $tracker->getId();
        }

        return $id;
    }

    private function buildCollectionOfArtifacts(array $results, int $total_size): ArtifactMatchingReportCollection
    {
        $artifacts = [];
        foreach ($results as $artifact) {
            $artifact = $this->artifact_factory->getArtifactById($artifact['id']);
            if ($artifact) {
                $artifacts[] = $artifact;
            }
        }

        return new ArtifactMatchingReportCollection($artifacts, $total_size);
    }
}
