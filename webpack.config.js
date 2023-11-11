const path = require("path");
const {CleanWebpackPlugin} = require("clean-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const WebpackAssetsManifest = require("webpack-assets-manifest");
const scriptRoot = "./src/main/resources/assets/script";

module.exports = {
    entry: {
        "main": `${scriptRoot}/main.ts`
    },
    output: {
        path: path.resolve(__dirname, "public/assets"),
        publicPath: "/assets/",
        filename: "[name].[contenthash].js"
    },
    optimization: {
        splitChunks: {
            cacheGroups: {
                styles: {
                    name: "styles",
                    type: "css/mini-extract",
                    chunks: "all",
                    enforce: true
                },
                vendor: {
                    test: /[\\/]node_modules[\\/].*\.js$/,
                    name: "vendor",
                    chunks: "all",
                }
            }
        }
    },
    devtool: "source-map",
    plugins: [
        new CleanWebpackPlugin(),
        new MiniCssExtractPlugin({
            filename: "[name].[contenthash].css"
        }),
        new WebpackAssetsManifest({
            output: path.resolve(__dirname, "webpack.assets.json"),
            publicPath: true
        })
    ],
    module: {
        rules: [
            {
                test: /\.tsx?$/,
                use: "ts-loader",
                exclude: /node_modules/
            },
            {
                test: /\.(scss)$/,
                use: [
                    MiniCssExtractPlugin.loader,
                    "css-loader",
                    {
                        loader: "postcss-loader",
                        options: {
                            postcssOptions: {
                                plugins: function () {
                                    return [
                                        require("autoprefixer")
                                    ];
                                }
                            }
                        }
                    },
                    "sass-loader"
                ]
            },
            {
                test: /.(png|jpg|ttf|otf|eot|svg|woff(2)?)(\?[a-z0-9]+)?$/,
                type: "asset/resource"
            }
        ]
    },
    resolve: {
        extensions: [".tsx", ".ts", ".js"]
    }
};