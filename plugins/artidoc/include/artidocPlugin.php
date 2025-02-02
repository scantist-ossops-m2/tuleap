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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

use Tuleap\Artidoc\ArtidocController;
use Tuleap\Artidoc\Document\ArtidocDao;
use Tuleap\Artidoc\Document\ArtidocDocument;
use Tuleap\Artidoc\Document\ArtidocRetriever;
use Tuleap\Docman\Item\GetDocmanItemOtherTypeEvent;
use Tuleap\Docman\REST\v1\GetOtherDocumentItemRepresentationWrapper;
use Tuleap\Docman\REST\v1\Search\SearchRepresentationOtherType;
use Tuleap\Document\Tree\OtherItemTypeDefinition;
use Tuleap\Document\Tree\OtherItemTypes;
use Tuleap\Plugin\ListeningToEventClass;
use Tuleap\Request\DispatchableWithRequest;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../../docman/vendor/autoload.php';

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ArtidocPlugin extends Plugin
{
    public function __construct(?int $id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-artidoc', __DIR__ . '/../site-content');
    }

    public function getPluginInfo(): PluginInfo
    {
        if ($this->pluginInfo === null) {
            $plugin_info = new PluginInfo($this);
            $plugin_info->setPluginDescriptor(
                new PluginDescriptor(
                    dgettext('tuleap-artidoc', 'Artidoc'),
                    dgettext('tuleap-artidoc', 'Artifacts as Documents'),
                )
            );
            $this->pluginInfo = $plugin_info;
        }

        return $this->pluginInfo;
    }

    public function getDependencies(): array
    {
        return ['tracker', 'docman'];
    }

    #[ListeningToEventClass]
    public function collectRoutesEvent(\Tuleap\Request\CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup('/artidoc', function (FastRoute\RouteCollector $r) {
            $r->get('/{id:\d+}[/]', $this->getRouteHandler('routeController'));
        });
    }

    public function routeController(): DispatchableWithRequest
    {
        return new ArtidocController(
            new ArtidocRetriever(
                ProjectManager::instance(),
                new ArtidocDao(),
                new Docman_ItemFactory(),
                $this,
            ),
            BackendLogger::getDefaultLogger(),
        );
    }

    #[ListeningToEventClass]
    public function getDocmanItemOtherTypeEvent(GetDocmanItemOtherTypeEvent $event): void
    {
        if ($event->type === ArtidocDocument::TYPE) {
            $event->setInstance(new ArtidocDocument($event->row));
        }
    }

    #[ListeningToEventClass]
    public function getOtherDocumentItemRepresentationWrapper(GetOtherDocumentItemRepresentationWrapper $event): void
    {
        if ($event->item instanceof ArtidocDocument) {
            $event->setType(ArtidocDocument::TYPE);
        }
    }

    #[ListeningToEventClass]
    public function searchRepresentationOtherType(SearchRepresentationOtherType $event): void
    {
        if ($event->item instanceof ArtidocDocument) {
            $event->setType(ArtidocDocument::TYPE);
        }
    }

    #[ListeningToEventClass]
    public function otherItemTypes(OtherItemTypes $event): void
    {
        $event->addType(
            ArtidocDocument::TYPE,
            new OtherItemTypeDefinition('fa-solid fa-tlp-tracker-circle document-document-icon')
        );
    }
}
