const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('referal_code_details', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    user_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    refered_by: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    refered_by_upper_level: {
      type: DataTypes.STRING(255),
      allowNull: false
    },
    referal_code: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    user_amount: {
      type: DataTypes.STRING(50),
      allowNull: true,
      defaultValue: "0"
    },
    refered_by_amount: {
      type: DataTypes.DOUBLE,
      allowNull: true
    },
    added_from: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    status: {
      type: DataTypes.TINYINT,
      allowNull: true
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: true
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    }
  }, {
    sequelize,
    tableName: 'referal_code_details',
    timestamps: false
  });
};
