const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('join_contest_detail_results', {
    id: {
      type: DataTypes.BIGINT,
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
    bonus_amount: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 0
    },
    winning_amount: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 0
    },
    deposit_cash: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 0
    },
    wallet_type: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 0
    },
    total_amount: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    admin_comission: {
      type: DataTypes.DECIMAL(10,2),
      allowNull: true,
      defaultValue: 0.00
    },
    isCanceled: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    }
  }, {
    sequelize,
    tableName: 'join_contest_detail_results',
    timestamps: false,
    indexes: [
      {
        name: "PRIMARY",
        unique: true,
        using: "BTREE",
        fields: [
          { name: "id" },
        ]
      },
      {
        name: "user_id",
        using: "BTREE",
        fields: [
          { name: "user_id" },
        ]
      },
      {
        name: "contest_id",
        using: "BTREE",
        fields: [
          { name: "contest_id" },
        ]
      },
      {
        name: "series_id",
        using: "BTREE",
        fields: [
          { name: "series_id" },
        ]
      },
      {
        name: "match_id",
        using: "BTREE",
        fields: [
          { name: "match_id" },
        ]
      },
    ]
  });
};
