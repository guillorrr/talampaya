// webpack.config.js

const path = require('path');

module.exports = {
	entry: {
		main: './patternlab/source/js/main.js',
		scripts: './src/theme/assets/scripts/scripts.js',
		backend: './src/theme/assets/scripts/backend.js',
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
	optimization: {
		minimize: process.env.NODE_ENV === 'production',
	},
	devtool: process.env.NODE_ENV === 'production' ? false : 'source-map',
};
