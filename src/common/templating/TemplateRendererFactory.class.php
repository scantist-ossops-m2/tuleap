<?php
/**
 * Copyright (c) Enalean, 2012-2017. All Rights Reserved.
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

use Tuleap\Templating\TemplateCache;

/**
 * Handles TemplateRenderer's instanciation. 
 */
class TemplateRendererFactory
{
    /**
     * @var TemplateCache
     */
    private $template_cache;

    public function __construct(TemplateCache $template_cache)
    {
        $this->template_cache = $template_cache;
    }

    /**
     * Creates a new factory instance.
     * 
     * Mostly used at places where renderers where instanciated manually, and
     * where injecting a factory needed a lot of refactoring.
     * 
     * @return \TemplateRendererFactory 
     */
    public static function build() {
        return new TemplateRendererFactory(new TemplateCache());
    }
    
    /**
     * Returns a new TemplateRenderer according to Config.
     * 
     * @param string $plugin_templates_dir
     * @return TemplateRenderer
     */
    public function getRenderer($plugin_templates_dir) {
        return new MustacheRenderer($this->template_cache, $plugin_templates_dir);
    }
}
