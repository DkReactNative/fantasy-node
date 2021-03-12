const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('dream_teams', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    match_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    series_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    player_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    points: {
      type: DataTypes.DOUBLE,
      allowNull: true
    }
  }, {
    sequelize,
    tableName: 'dream_teams',
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
        name: "match_id",
        using: "BTREE",
        fields: [
          { name: "match_id" },
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
        name: "player_id",
        using: "BTREE",
        fields: [
          { name: "player_id" },
        ]
      },
    ]
  });
};
