var webpack = require('webpack');
var ExtractTextPlugin = require('extract-text-webpack-plugin');
var CopyWebpackPlugin = require('copy-webpack-plugin');
var path = require('path');
var dir = 'js/dist';

module.exports = {
	entry: {
    	'ptws': './js/src/ptws.ts'
   	},
	output: {
   		path: path.join(__dirname, dir),
      	filename: "[name].js",
      	library: 'ptws',
      	libraryTarget: 'window'
   	},
	devtool: "source-map",
	module: {
		rules: [ 
            {
                test: /\.ts(x?)$/,
                exclude: /node_modules/,
                use: [
                    {
                        loader: "ts-loader"
                    }
                ]
            },
			{ 
				test: /.jsx?$/,
				loader: 'babel-loader',
				exclude: /node_modules/
			},
			{
				test: /\.js$/,
				exclude: /node_modules/,
				loader: ['babel-loader', 'eslint-loader']
			},
			{
				test: /\.(jpe?g|gif|png|svg)$/,
				loader: "file-loader",
				options: {
					name: 'img/[name].[ext]',
					publicPath: '../'
				}
			},
			{
				test: /\.(woff(2)?|ttf|eot|svg)(\?v=\d+\.\d+\.\d+)?$/,
				loader: 'file-loader',
				options: {
					name: 'fonts/[name].[ext]',
					publicPath: '../'
				}
			},
			{
				test: /\.scss$/,
				use: ExtractTextPlugin.extract({
					fallback: "style-loader",
					use: [
						{ loader: 'css-loader',
							options: {
								sourceMap: true
							}
						},
						{ loader: 'postcss-loader',
							options: {
								sourceMap: true
							}
						},
						{ loader: 'sass-loader',
							options: {
								sourceMap: true,
								outputStyle: 'expanded'
							},
						}
					]
				}),
				exclude: /node_modules/,
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
		"react": "React",
        "jquery": "jQuery",
		"@wordpress/components": { this: [ 'wp', 'components' ]},
//		"@wordpress/element": { this: [ 'wp', 'element' ]},
		"@wordpress/blocks": { this: [ 'wp', 'blocks' ]},
		"@wordpress/editor": { this: [ 'wp', 'editor' ]},
//		"@wordpress/utils": { this: [ 'wp', 'utils' ]},
//		"@wordpress/date": { this: [ 'wp', 'date' ]},
//		"@wordpress/data": { this: [ 'wp', 'data' ]},
//		"@wordpress/compose": { this: [ 'wp', 'compose' ]}
    },
	plugins: [
		new ExtractTextPlugin({ filename: 'css/[name].css' }),
		new webpack.ProvidePlugin({
			'ptws': 'ptws'
		})
	]
};
