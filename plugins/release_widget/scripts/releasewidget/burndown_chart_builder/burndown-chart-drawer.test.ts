/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { createBurndownChart, getMaxRemainingEffort } from "./burndown-chart-drawer";

import { BurndownData, PointsWithDate } from "../src/type";
import { ChartPropsBurndownWhithoutTooltip } from "../../../../../src/www/scripts/charts-builders/type";
jest.mock("../../../../../src/www/scripts/charts-builders/time-scale-labels-formatter");
jest.mock("./burndown-time-scale-label-formatter");

describe("BurndownChartDrawer -", () => {
    describe("getMaxRemainingEffort -", () => {
        it("Returns the highest remaining effort if it is greater than the capacity", () => {
            const max_remaining_effort = getMaxRemainingEffort({
                points_with_date: getPointsWithDateWithMaxIs15(),
                capacity: 10
            } as BurndownData);

            expect(max_remaining_effort).toEqual(15);
        });

        it("Returns capacity if it is greater than the biggest remaining effort", () => {
            const max_remaining_effort = getMaxRemainingEffort({
                points_with_date: getPointsWithDateWithMaxIs15(),
                capacity: 20
            } as BurndownData);

            expect(max_remaining_effort).toEqual(20);
        });

        it("When there are some remaining effort and no capacity, Then the biggest remaining effort is returned", () => {
            const max_remaining_effort = getMaxRemainingEffort({
                points_with_date: getPointsWithDateWithMaxIs15(),
                capacity: null
            } as BurndownData);

            expect(max_remaining_effort).toEqual(15);
        });

        it("When there aren't capacity and remaining effort, Then 5 is returned", () => {
            const max_remaining_effort = getMaxRemainingEffort({
                points_with_date: getPointsWithDateWithMaxIsNull(),
                capacity: null
            } as BurndownData);

            expect(max_remaining_effort).toEqual(5);
        });

        it("When there aren't remaining effort but there is capacity, Then capacity is returned", () => {
            const max_remaining_effort = getMaxRemainingEffort({
                points_with_date: getPointsWithDateWithMaxIsNull(),
                capacity: 100
            } as BurndownData);

            expect(max_remaining_effort).toEqual(100);
        });

        it("When capacity and remaining effort are 0, Then 5 is returned", () => {
            const max_remaining_effort = getMaxRemainingEffort({
                points_with_date: getPointsWithDateWithMaxIsZero(),
                capacity: 0
            } as BurndownData);

            expect(max_remaining_effort).toEqual(5);
        });
    });

    describe("createBurndownChart -", () => {
        it("When the chart is created, Then there are a G element and 2 lines scale", () => {
            const chart_svg_element = getDocument();
            createBurndownChart(chart_svg_element, getChartProps(), getBurndownData());

            expect(chart_svg_element).toMatchSnapshot();
        });

        it("When there isn't points with date in burndownData, Then there is an empty graph", () => {
            const chart_svg_element = getDocument();
            createBurndownChart(
                chart_svg_element,
                getChartProps(),
                getBurndownDataWithoutPointsWithDate()
            );

            expect(chart_svg_element.childElementCount).toEqual(1);
        });

        function getDocument(): HTMLElement {
            const local_document = document.implementation.createHTMLDocument();
            const chart_div = local_document.createElement("svg");
            chart_div.setAttribute("id", "chart-100");
            return chart_div;
        }
    });

    function getChartProps(): ChartPropsBurndownWhithoutTooltip {
        return {
            graph_width: 100,
            graph_height: 100,
            margins: {
                top: 20,
                right: 20,
                bottom: 20,
                left: 20
            }
        };
    }

    function getBurndownData(): BurndownData {
        return {
            opening_days: [1, 2, 3, 4, 5],
            duration: 1,
            start_date: "2019-07-01T00:00:00+00:00",
            capacity: null,
            is_under_calculation: false,
            points: [5, 10, 15],
            points_with_date: getPointsWithDateWithMaxIs15()
        };
    }

    function getBurndownDataWithoutPointsWithDate(): BurndownData {
        return {
            opening_days: [1, 2, 3, 4, 5],
            duration: 1,
            start_date: "2019-07-01T00:00:00+00:00",
            capacity: null,
            is_under_calculation: false,
            points: [],
            points_with_date: []
        };
    }

    function getPointsWithDateWithMaxIs15(): PointsWithDate[] {
        const points: PointsWithDate[] = [];
        points.push({
            date: "2019-07-01T00:00:00+00:00",
            remaining_effort: null
        });
        points.push({
            date: "2019-07-01T00:00:00+00:00",
            remaining_effort: 15
        });

        return points;
    }

    function getPointsWithDateWithMaxIsNull(): PointsWithDate[] {
        const points: PointsWithDate[] = [];
        points.push({
            date: "2019-07-01T00:00:00+00:00",
            remaining_effort: null
        });

        return points;
    }

    function getPointsWithDateWithMaxIsZero(): PointsWithDate[] {
        const points: PointsWithDate[] = [];
        points.push({
            date: "2019-07-01T00:00:00+00:00",
            remaining_effort: 0
        });

        return points;
    }
});
