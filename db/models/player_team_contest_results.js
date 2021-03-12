const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('player_team_contest_results', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    player_team_result_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    match_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    series_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    contest_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    quiz_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    user_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    rank: {
      type: DataTypes.BIGINT,
      allowNull: true,
      defaultValue: 0
    },
    old_rank: {
      type: DataTypes.BIGINT,
      allowNull: false
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
    match_start_notification: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    match_end_notification: {
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
    created: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    }
  }, {
    sequelize,
    tableName: 'player_team_contest_results',
    timestamps: false
  });
};
