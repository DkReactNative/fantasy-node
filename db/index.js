"use strict";
const {
  DB_HOST,
  MASTER_HOST,
  SLAVE_HOST,
  DB_NAME,
  DB_USER,
  DB_PASSWORD,
  DB_PORT,
  REDIS_CACHE_TTL,
} = process.env;
const moment = require("moment");
const sequelizeCache = require("sequelize-transparent-cache");
const { applyExtraSetup } = require("./extra-setup");
const { Sequelize } = require("sequelize");
const fs = require("fs");
const path = require("path");
const { pascalCase } = require("change-case");
const _ = require("lodash");

const getRandomWithinRange = (min, max) => {
  min = Math.ceil(min);
  max = Math.floor(max);
  return Math.floor(Math.random() * (max - min + 1)) + min; // The maximum is inclusive and the minimum is inclusive
};
const maxConnectionAge = moment.duration(10, "minutes").asSeconds();

const pool = {
  handleDisconnects: true,
  min: 1, // Keep one connection open
  max: 10, // Max 10 connections
  idle: 9000, // 9 secondes
  validate: (obj) => {
    // Recycle connexions periodically
    if (!obj.recycleWhen) {
      // Setup expiry on new connexions and return the connexion as valid
      obj.recycleWhen = moment().add(
        getRandomWithinRange(maxConnectionAge, maxConnectionAge * 2),
        "seconds"
      );
      return true;
    }
    // Recycle the connexion if it has expired
    return moment().diff(obj.recycleWhen, "seconds") < 0;
  },
};
console.log(DB_NAME, DB_USER, DB_PASSWORD, DB_HOST)
const sequelize = new Sequelize(DB_NAME, DB_USER, DB_PASSWORD, {
  dialect: "mysql",
  host: DB_HOST,
  port: DB_PORT,
  logging: true,
  dialectOptions: {
    useUTC: true,
  },
  timezone: "+00:00",
  pool: pool,
});

sequelize
  .authenticate()
  .then(() => {
    console.log('Connection has been established successfully.');
  })
  .catch((err) => {
    console.log('Unable to connect to the database:', JSON.stringify(err));
  });

// Constants
const MODELS_DIRECTORY_PATH = path.resolve(__dirname, "models");

// Initialize Sequelize Connection
const importModels = () => {
  //
  const models = {};
  //
  fs.readdirSync(MODELS_DIRECTORY_PATH)
    .filter((file) => file.indexOf(".") !== 0 && file !== "index.js")
    .forEach((file) => {
      const model = require(path.join(MODELS_DIRECTORY_PATH, file))(
        sequelize,
        Sequelize.DataTypes
      );
      let name = _.upperFirst(_.camelCase(model.name));
      models[name] = model;
    });
  return models;
};

// Initialize Sequelize Models
sequelize.models = importModels();
applyExtraSetup(sequelize);
module.exports = sequelize;
