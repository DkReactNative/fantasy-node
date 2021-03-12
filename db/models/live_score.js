const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('live_score', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    seriesId: {
      type: DataTypes.BIGINT,
      allowNull: true
    },
    matchId: {
      type: DataTypes.BIGINT,
      allowNull: true
    },
    teamId: {
      type: DataTypes.BIGINT,
      allowNull: true
    },
    teamType: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    matchType: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    matchStatus: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    comment: {
      type: DataTypes.TEXT,
      allowNull: true
    },
    playerId: {
      type: DataTypes.BIGINT,
      allowNull: true
    },
    playerName: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    point: {
      type: DataTypes.STRING(10),
      allowNull: true,
      defaultValue: "0"
    },
    run_scored: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    status: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    ball_faced: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    s4: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    s6: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    battingStrikeRate: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    isCurrentBatsman: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 0
    },
    inning_number: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    extra_run_scored: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    bowls: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    wickets: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    total_inning_score: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    run_rate: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    over_bowled: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    maidens_bowled: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    runs_conceded: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    wickets_taken: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    wide_balls: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    economy_rates_runs_conceded: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    no_balls: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    isCurrentBowler: {
      type: DataTypes.TINYINT,
      allowNull: true,
      defaultValue: 0
    },
    stampCount: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    run_out_count: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 0
    },
    runout_thrower: {
      type: DataTypes.INTEGER,
      allowNull: false,
      defaultValue: 0
    },
    runout_catcher: {
      type: DataTypes.INTEGER,
      allowNull: false,
      defaultValue: 0
    },
    catch: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    is_lineup: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 2
    },
    from_es: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    },
    modified: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    }
  }, {
    sequelize,
    tableName: 'live_score',
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
        name: "playerId",
        using: "BTREE",
        fields: [
          { name: "playerId" },
        ]
      },
      {
        name: "teamId",
        using: "BTREE",
        fields: [
          { name: "teamId" },
        ]
      },
    ]
  });
};
