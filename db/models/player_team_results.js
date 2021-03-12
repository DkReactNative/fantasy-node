const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('player_team_results', {
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
    captain: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    vice_captain: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    twelveth: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    replace_player_ids: {
      type: DataTypes.STRING(50),
      allowNull: false
    },
    replaced_by: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    substitute: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    substitute_status: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    points: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 0
    },
    status: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 1
    },
    team_count: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    trump_mode: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true
    }
  }, {
    sequelize,
    tableName: 'player_team_results',
    timestamps: false
  });
};
