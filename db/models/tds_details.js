const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('tds_details', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    match_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    contest_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    user_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    winning_amount: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    tds_amount: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    win_tds_amount: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    winning_date: {
      type: DataTypes.DATEONLY,
      allowNull: true
    }
  }, {
    sequelize,
    tableName: 'tds_details',
    timestamps: false
  });
};
