const path = require('path');
const TerserPlugin = require('terser-webpack-plugin');
module.exports = {
	mode: process.env.NODE_ENV || 'development',
	entry: './source/js/main.js',
	output: {
		filename: 'script.js',
		path: path.resolve(__dirname, 'public/js'),
	},
	devtool: process.env.NODE_ENV === 'production' ? 'source-map' : 'eval-source-map',
	...(process.env.NODE_ENV === 'production' && {
		optimization: {
			minimize: true,
			minimizer: [
				new TerserPlugin({
					terserOptions: { format: { comments: false } },
					extractComments: false,
				}),
			],
		},
	}),
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: ['@babel/preset-env'],
					},
				},
			},
		],
	},
};
