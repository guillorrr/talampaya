// webpack.config.js

const path = require('path');

module.exports = {
	entry: {
		main: './src/assets/js/main.js',
	},
	output: {
		filename: '[name].min.js',
		path: path.resolve(__dirname, 'dist/js'),
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				exclude: /node_modules/,
				loader: 'babel-loader',
				options: {
					presets: ['@babel/preset-env'],
				},
			},
		],
	},
	mode: process.env.NODE_ENV == 'production' ? 'production' : 'development',
};
