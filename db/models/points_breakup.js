const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('points_breakup', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    series_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    match_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    player_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    inning_number: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    in_starting: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    in_starting_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    runs: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    runs_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    fours: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    fours_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    sixes: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    sixes_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    strike_rate: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    strike_rate_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    century_halfCentury: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    century_halfCentury_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    duck_out: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    duck_out_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    wickets: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    wickets_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    maiden_over: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    maiden_over_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    economy_rate: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    economy_rate_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    bonus: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    bonus_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    catch: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    catch_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    run_outStumping: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    run_outStumping_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    run_out: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    run_out_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    runout_thrower: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    runout_thrower_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    runout_catcher: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    runout_catcher_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    total_point: {
      type: DataTypes.STRING(20),
      allowNull: true
    },
    created: {
      type: DataTypes.DATE,
      allowNull: true,
      defaultValue: Sequelize.fn('current_timestamp')
    }
  }, {
    sequelize,
    tableName: 'points_breakup',
    timestamps: false
  });
};
