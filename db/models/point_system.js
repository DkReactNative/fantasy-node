const Sequelize = require('sequelize');
module.exports = function(sequelize, DataTypes) {
  return sequelize.define('point_system', {
    id: {
      type: DataTypes.INTEGER,
      allowNull: false,
      primaryKey: true
    },
    matchType: {
      type: DataTypes.STRING(5),
      allowNull: true,
      comment: "1=T20, 2=ODI, 3=Test, 4=T10"
    },
    battingRun: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 0.5
    },
    battingBoundary: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 0.5
    },
    battingSix: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 1
    },
    battingHalfCentury: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 4
    },
    battingCentury: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 8
    },
    battingDuck: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -2
    },
    t10Bonus30Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 2
    },
    t10Bonus50Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 4
    },
    t10bowling2Wicket: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 5
    },
    t10bowling3Wicket: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 8
    },
    bowlingWicket: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 10
    },
    bowling4Wicket: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 4
    },
    bowling5Wicket: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 8
    },
    bowlingMaiden: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 4
    },
    bowlingDotBall: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 1
    },
    fieldingCatch: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 4
    },
    fieldingStumpRunOut: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 6
    },
    fieldingRunOutThrower: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 4
    },
    fieldingRunOutCatcher: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 2
    },
    othersCaptain: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 2
    },
    othersViceCaptain: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 1.5
    },
    othersStarting11: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 2
    },
    t20EconomyLt4Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 3
    },
    t20EconomyGt4Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 2
    },
    t20EconomyGt5Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 1
    },
    t20EconomyGt9Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -1
    },
    t20EconomyGt10Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -2
    },
    t20EconomyGt11Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -3
    },
    odiEconomyLt2_5Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 3
    },
    odiEconomyGt2_5Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 2
    },
    odiEconomyGt3_5Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 1
    },
    odiEconomyGt5Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -1
    },
    odiEconomyGt8Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -2
    },
    odiEconomyGt9Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -3
    },
    t10EconomyLt6Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 3
    },
    t10EconomyGt6Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 2
    },
    t10EconomyBt7_8Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: 1
    },
    t10EconomyBt11_12Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -1
    },
    t10EconomyBt12_13Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -2
    },
    t10EconomyGt13Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -3
    },
    t20StrikeLt50Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -3
    },
    t20StrikeGt50Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -2
    },
    t20StrikeGt60Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -1
    },
    odiStrikeLt40Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -3
    },
    odiStrikeGt40Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -2
    },
    odiStrikeGt50Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -1
    },
    t10StrikeGt90Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -1
    },
    t10StrikeBt80_90Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -2
    },
    t10StrikeLt80Runs: {
      type: DataTypes.DOUBLE,
      allowNull: true,
      defaultValue: -3
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
    tableName: 'point_system',
    timestamps: false
  });
};
