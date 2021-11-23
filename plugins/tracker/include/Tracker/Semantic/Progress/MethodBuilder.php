<?php
/**
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

declare(strict_types=1);

namespace Tuleap\Tracker\Semantic\Progress;

use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;

class MethodBuilder
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;
    /**
     * @var SemanticProgressDao
     */
    private $dao;
    /**
     * @var TypePresenterFactory
     */
    private $natures_factory;

    public function __construct(
        \Tracker_FormElementFactory $form_element_factory,
        SemanticProgressDao $dao,
        TypePresenterFactory $natures_factory
    ) {
        $this->form_element_factory = $form_element_factory;
        $this->dao                  = $dao;
        $this->natures_factory      = $natures_factory;
    }

    public function buildMethodBasedOnEffort(
        \Tracker $tracker,
        int $total_effort_field_id,
        int $remaining_effort_field_id
    ): IComputeProgression {
        if ($total_effort_field_id === $remaining_effort_field_id) {
            return new InvalidMethod(
                dgettext(
                    'tuleap-tracker',
                    'Progress semantic is not properly configured: total effort and remaining effort fields have to be two different fields.'
                )
            );
        }

        $total_effort_field = $this->form_element_factory->getUsedFieldByIdAndType(
            $tracker,
            $total_effort_field_id,
            ['int', 'float', 'computed']
        );

        $remaining_effort_field = $this->form_element_factory->getUsedFieldByIdAndType(
            $tracker,
            $remaining_effort_field_id,
            ['int', 'float', 'computed']
        );

        if (! $total_effort_field instanceof \Tracker_FormElement_Field_Numeric) {
            return new InvalidMethod(
                dgettext('tuleap-tracker', 'Progress semantic is not properly configured: unable to find the total effort field.')
            );
        }

        if (! $remaining_effort_field instanceof \Tracker_FormElement_Field_Numeric) {
            return new InvalidMethod(
                dgettext('tuleap-tracker', 'Progress semantic is not properly configured: unable to find the remaining effort field.')
            );
        }

        return new MethodBasedOnEffort(
            $this->dao,
            $total_effort_field,
            $remaining_effort_field
        );
    }

    public function buildMethodBasedOnChildCount(
        \Tracker $tracker,
        string $link_type
    ): IComputeProgression {
        $artifact_links_fields = $this->form_element_factory->getUsedArtifactLinkFields($tracker);
        if (empty($artifact_links_fields)) {
            return new InvalidMethod(
                sprintf(
                    dgettext(
                        'tuleap-tracker',
                        'Progress semantic is not properly configured: Unable to find an artifact link field in tracker %s.'
                    ),
                    $tracker->getName()
                )
            );
        }

        if ($link_type !== \Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD) {
            return new InvalidMethod(
                dgettext(
                    'tuleap-tracker',
                    'Progress semantic is not properly configured: Only links of type "Child" are supported.'
                )
            );
        }

        $nature = $this->natures_factory->getTypeEnabledInProjectFromShortname($tracker->getProject(), $link_type);
        if ($nature === null) {
            return new InvalidMethod(
                sprintf(
                    dgettext(
                        'tuleap-tracker',
                        'Progress semantic is not properly configured: Link type %s is not activated in the project or does not exist.'
                    ),
                    $link_type
                )
            );
        }

        return new MethodBasedOnLinksCount(
            $this->dao,
            $link_type
        );
    }

    public function buildMethodFromRequest(\Tracker $tracker, \Codendi_Request $request): IComputeProgression
    {
        $method = $request->get('computation-method');

        switch ($method) {
            case MethodBasedOnEffort::getMethodName():
                return $this->buildMethodBasedOnEffort(
                    $tracker,
                    (int) $request->get('total-effort-field-id'),
                    (int) $request->get('remaining-effort-field-id'),
                );
            case MethodBasedOnLinksCount::getMethodName():
                return $this->buildMethodBasedOnChildCount(
                    $tracker,
                    \Tracker_FormElement_Field_ArtifactLink::NATURE_IS_CHILD
                );
            default:
                return new InvalidMethod(
                    dgettext(
                        'tuleap-tracker',
                        'Provided computation method does not exist'
                    )
                );
        }
    }
}
