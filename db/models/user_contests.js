const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('user_contests', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    user_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    contest_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    series_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    match_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    contest_name: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: true
    }
  }, {
    sequelize,
    tableName: 'user_contests',
    timestamps: false
  });
};
