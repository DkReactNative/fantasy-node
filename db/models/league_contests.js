const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('league_contests', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    user_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    points: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 0
    },
    rank: {
      type: DataTypes.BIGINT,
      allowNull: true,
      defaultValue: 0
    },
    counter: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    previous_rank: {
      type: DataTypes.BIGINT,
      allowNull: true,
      defaultValue: 0
    },
    winning_amount: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    contest_start_notification: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    contest_end_notification: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    winning_amount_notification: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    is_croned: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    flag: {
      type: DataTypes.INTEGER,
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
    tableName: 'league_contests',
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
        name: "rank",
        using: "BTREE",
        fields: [
          { name: "rank" },
        ]
      },
      {
        name: "winning_amount",
        using: "BTREE",
        fields: [
          { name: "winning_amount" },
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
        name: "match_start_notification",
        using: "BTREE",
        fields: [
          { name: "contest_start_notification" },
        ]
      },
      {
        name: "match_end_notification",
        using: "BTREE",
        fields: [
          { name: "contest_end_notification" },
        ]
      },
      {
        name: "winning_amount_notification",
        using: "BTREE",
        fields: [
          { name: "winning_amount_notification" },
        ]
      },
    ]
  });
};
