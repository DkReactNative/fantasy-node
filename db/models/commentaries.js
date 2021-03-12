const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('commentaries', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    seriesId: {
      type: DataTypes.STRING(20),
      allowNull: false
    },
    matchId: {
      type: DataTypes.STRING(20),
      allowNull: false
    },
    teamId: {
      type: DataTypes.STRING(20),
      allowNull: false
    },
    commentari_id: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    over: {
      type: DataTypes.STRING(10),
      allowNull: false
    },
    post: {
      type: DataTypes.TEXT,
      allowNull: false
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
    tableName: 'commentaries',
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
        name: "seriesId",
        using: "BTREE",
        fields: [
          { name: "seriesId" },
        ]
      },
      {
        name: "matchId",
        using: "BTREE",
        fields: [
          { name: "matchId" },
        ]
      },
      {
        name: "teamId",
        using: "BTREE",
        fields: [
          { name: "teamId" },
        ]
      },
      {
        name: "commentari_id",
        using: "BTREE",
        fields: [
          { name: "commentari_id" },
        ]
      },
    ]
  });
};
