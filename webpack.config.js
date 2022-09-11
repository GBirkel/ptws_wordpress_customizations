var webpack = require('webpack');
var path = require('path');
var dir = 'js';

module.exports = {
	entry: {
    	'ptws': [
			path.resolve(__dirname, './js-src/block-itinerary.ts'),
			path.resolve(__dirname, './js-src/ptws.ts')
		]
   	},
	output: {
   		path: path.join(__dirname, dir),
      	filename: "[name].js",
      	library: 'ptws',
      	libraryTarget: 'window'
   	},
	resolve: {
        modules: [
            path.resolve("node_modules"),
            path.resolve(__dirname, './js-src/'),
        ],
        extensions: [ ".ts", ".js"]
    },
	devtool: "source-map",
	module: {
		rules: [ 
            {
                test: /\.ts(x?)$/,
                exclude: /node_modules/,
				use: ['ts-loader']
            },
			{
				test: /\.js$/,
				exclude: /node_modules/,
				use:{ 
				 loader:'babel-loader', 
				 options:{
				  presets:['@babel/preset-env'],
				  plugins:['@babel/plugin-proposal-class-properties']
				 }
				}
			},
			{
				test: /\.(jpe?g|gif|png|svg)$/,
				use: {
					loader:'file-loader', 
					options: {
						name: 'img/[name].[ext]',
						publicPath: '../'
					}
				}   
			},
			{
				test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
				use: {
					loader:'file-loader', 
					options: {
						name: 'fonts/[name].[ext]',
						publicPath: '../'
					}
				}   
			},
			{
				test: /\.css$/,
				use: ['style-loader', 'css-loader']
			},
            // All output '.js' files will have any sourcemaps re-processed by 'source-map-loader'.
            {
                enforce: "pre",
                test: /\.js$/,
                loader: "source-map-loader"
            }
		]
	},
    externals: {
//		"react": "React",
        "jquery": "jQuery",
		"@wordpress/block-editor": 'wp.blockEditor',
		"@wordpress/components": 'wp.components',
		"@wordpress/element": 'wp.element',
		"@wordpress/blocks": 'wp.blocks',
		"@wordpress/editor": 'wp.editor',
		"@wordpress/utils": 'wp.utils',
		"@wordpress/date": 'wp.date',
		"@wordpress/data": 'wp.data',
		"@wordpress/compose": 'wp.compose',
		"@wordpress/i18n": 'wp.i18n'
    },
	plugins: [
		new webpack.ProvidePlugin({
			'ptws': 'ptws'
		})
	]
};
