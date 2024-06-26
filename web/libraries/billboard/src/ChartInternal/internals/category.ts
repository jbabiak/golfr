/**
 * Copyright (c) 2017 ~ present NAVER Corp.
 * billboard.js project is licensed under the MIT license
 */
export default {
	/**
	 * Category Name
	 * @param {number} i Index number
	 * @returns {string} category Name
	 * @private
	 */
	categoryName(i: number): string {
		const {axis_x_categories} = this.config;

		return axis_x_categories?.[i] ?? i;
	},
};
