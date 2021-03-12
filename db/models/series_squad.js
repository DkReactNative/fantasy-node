const Sequelize = require('sequelize');
module.exports = function (sequelize, DataTypes) {
  return sequelize.define('series_squad', {
    id: {
      type: DataTypes.BIGINT,
      allowNull: false,
      primaryKey: true
    },
    series_id: {
      type: DataTypes.INTEGER,
      allowNull: true,
      references: {
        model: 'series',
        key: 'id'
      }
    },
    date: {
      type: DataTypes.DATEONLY,
      allowNull: true
    },
    time: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    enddate: {
      type: DataTypes.DATEONLY,
      allowNull: false
    },
    endtime: {
      type: DataTypes.STRING(20),
      allowNull: false
    },
    type: {
      type: DataTypes.STRING(40),
      allowNull: true
    },
    match_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    localteam: {
      type: DataTypes.STRING(100),
      allowNull: true
    },
    localteam_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    localteam_score: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    localteam_stat: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    localteam_players: {
      type: DataTypes.TEXT,
      allowNull: false
    },
    visitorteam: {
      type: DataTypes.STRING(100),
      allowNull: true
    },
    visitorteam_id: {
      type: DataTypes.INTEGER,
      allowNull: true
    },
    visitorteam_score: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    visitorteam_stat: {
      type: DataTypes.STRING(255),
      allowNull: true
    },
    visitorteam_players: {
      type: DataTypes.TEXT,
      allowNull: false
    },
    status: {
      type: DataTypes.INTEGER,
      allowNull: true,
      defaultValue: 1,
      comment: "0=not active, 1=active"
    },
    match_status: {
      type: DataTypes.STRING(50),
      allowNull: true
    },
    guru_url: {
      type: DataTypes.TEXT,
      allowNull: true
    },
    slug: {
      type: DataTypes.STRING(100),
      allowNull: true
    },
    win_flag: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 1,
      comment: "0=>Not distributed,1=>distributed"
    },
    manOf: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    is_rank_updated: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    allow_prize_distribution: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 1,
      comment: "1-Allow,2-Not Allow"
    },
    is_player_fetched: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    is_lineup: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    is_cancelled: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    cancel_allow: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 1
    },
    es_verified: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    es_pre_squad: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    match_noti: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    lineup_noti: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    is_publish: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    prize_destributed: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    inning1st_status: {
      type: DataTypes.INTEGER,
      allowNull: false,
      defaultValue: 0
    },
    inning2nd_status: {
      type: DataTypes.INTEGER,
      allowNull: false
    },
    inning_break_status: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    manual_inning_break_status: {
      type: DataTypes.TINYINT,
      allowNull: false,
      defaultValue: 0
    },
    mega_prize: {
      type: DataTypes.STRING(15),
      allowNull: false
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
    tableName: 'series_squad',
    timestamps: false
  });
};
