const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('user_contest_breakup', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    contest_size_start: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    contest_size_end: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    winner: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    rank: {
      type: DataTypes.STRING(150),
      allowNull: true
    },
    percent_prize: {
      type: DataTypes.DOUBLE,
      allowNull: true
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true
    }
  }, {
    sequelize,
    tableName: 'user_contest_breakup',
    timestamps: false
  });
};
