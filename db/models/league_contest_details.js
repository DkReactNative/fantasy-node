const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('league_contest_details', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    league_contest_id: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    user_id: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    series_squad_id: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    match_id: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    match_date: {
      type: DataTypes.DATEONLY,
      allowNull: false
    },
    match_point: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    contest_joined: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    contest_joined_point: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    amount_spent: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    amount_spent_point: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    max_team_point: {
      type: DataTypes.DECIMAL(11,2),
      allowNull: false
    },
    mega_point: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 0
    },
    created: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: Sequelize.fn('current_timestamp')
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: false,
      defaultValue: Sequelize.fn('current_timestamp')
    }
  }, {
    sequelize,
    tableName: 'league_contest_details',
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
        unique: true,
        using: "BTREE",
        fields: [
          { name: "user_id" },
          { name: "match_id" },
        ]
      },
      {
        name: "player_mega_contest_id",
        using: "BTREE",
        fields: [
          { name: "league_contest_id" },
        ]
      },
      {
        name: "contest_id",
        using: "BTREE",
        fields: [
          { name: "user_id" },
        ]
      },
      {
        name: "series_squad_id",
        using: "BTREE",
        fields: [
          { name: "series_squad_id" },
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
