const path = require("path");
const glob = require("glob");
const TerserPlugin = require("terser-webpack-plugin");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");

module.exports = (env, argv) => {
  const entries = {};
  glob.sync("./components/*/*.js").forEach((file) => {
    entries[path.parse(file).name] = file;
  });
  return {
    mode: argv.mode ? argv.mode : "development",
    devtool: argv.mode !== "production" ? "source-map" : false,
    stats: "errors-only",
    infrastructureLogging: { appendOnly: true, level: "log" },
    watchOptions: { poll: 1000 },
    entry: entries,
    module: {
      rules: [
        {
          test: /\.scss$/,
          include: path.resolve(".", "components"),
          use: [
            MiniCssExtractPlugin.loader,
            "css-loader",
            "postcss-loader",
            "sass-loader",
          ],
        },
        {
          test: /\.js$/,
          include: path.resolve(".", "components"),
          use: [
            {
              loader: "babel-loader",
              options: {
                presets: [["@babel/preset-env", { modules: "commonjs" }]],
                cacheDirectory: true,
              },
            },
          ],
        },
      ],
    },
    optimization: {
      removeAvailableModules: false,
      minimizer: [new TerserPlugin({ extractComments: false })],
    },
    output: {
      path: path.resolve(".", "public"),
      filename: "js/[name].js",
    },
    plugins: [new MiniCssExtractPlugin({ filename: "css/[name].css" })],
  };
};
