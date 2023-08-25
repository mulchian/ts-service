const AJAX_LIVE_URL = window.location.origin + '/ajax/live/';
const TBL_STANDINGS = $('#tblStandings');
const LIVE_VIEW = $('#colLive');
// calculate innerWidth and innerHeight of liveView
const width = LIVE_VIEW.width();
const height = LIVE_VIEW.parent().height() >= 540 ? LIVE_VIEW.parent().height() : 540;

let startTime = Number.MAX_SAFE_INTEGER,
    game;

export default class Game {
    init() {
        console.log("Pixi7 initializing...");
        this.app = new PIXI.Application({
            autoResize: true,
            autoDensity: true,
            view: document.getElementById('liveView'),
            backgroundColor: 0x006400,
            resolution: window.devicePixelRatio || 1,
            width: width,
            height: height
        });
        this.PRELOADED = false;
        console.log("Pixi7 initialized...");
        document.getElementById('colLive').appendChild(this.app.view);
        window.addEventListener('resize', () => {
            this.resize()
        }, false);
        this.resize();
        this.preload();

        this.playTicker = new PIXI.Ticker();
        this.playTicker.add((delta) => {
            this.play(delta);
        });
        this.playTicker.start();
    }

    resize() {
        let colHome = $('#colHome');
        this.parentWidth = LIVE_VIEW.width();
        this.parentHeight = colHome.find('ul').height() >= 540 ? colHome.find('ul').height() : 540;
        this.app.renderer.resize(this.parentWidth, this.parentHeight);
        console.log("resizing...");
        this.DEADCENTER_H = this.parentWidth / 2;
        if (!this.actualFootballPosition) {
            this.actualFootballPosition = this.DEADCENTER_H - 16;
        }
        if (this.PRELOADED) {
            //clear stage and re-render, preload not necessary as assets are loaded already;
            this.app.stage.removeChildren();
            this.drawScene(this.actualFootballPosition);
            this.updateText(this.gameText.text);
        }
    }

    async preload() {
        this.loadingSpriteText = new PIXI.Text('Loading...', {
            fontFamily: 'Arial',
            fontSize: 18,
            fontWeight: "bold",
            fill: 0x999999,
            align: 'center'
        });
        this.loadingSpriteText.anchor.set(0.5);
        this.loadingSpriteText.x = (this.parentWidth / 2);
        this.loadingSpriteText.y = (this.parentHeight / 2);
        this.app.stage.addChild(this.loadingSpriteText);
        // it seems if you have json animated sprite from texture packer,
        // make sure the json file is sit together with the png file.
        PIXI.Assets.addBundle('pipeline', {
            football: '/resources/images/football.png'
        });
        this.assetPipeline = await PIXI.Assets.loadBundle('pipeline', (evt) => {
            this.onProgress(evt)
        });
        this.drawScene(this.actualFootballPosition);
    }

    onProgress(evt) {
        if (evt === 1) {
            this.loadingSpriteText.text = "Loading Complete...";
            this.PRELOADED = true;
            this.loadingSpriteText.destroy();
        } else {
            this.loadingSpriteText.text = `Loading ${Math.ceil(evt) * 100}%...`;
        }
    }

    drawScene() {
        this.fieldContainer = new PIXI.Container();
        this.fieldTextsContainer = new PIXI.Container();

        if (!this.footballContainer) {
            this.footballContainer = new PIXI.Container();
            //create football object
            let football = this.createNewFootball();
            this.footballContainer.addChild(football);
        }
        if (!this.footballLineContainer) {
            this.footballLineContainer = new PIXI.Container();
        }

        // build the field
        this.FIELD_WIDTH = this.parentWidth * 0.8;

        let fieldBackground = new PIXI.Graphics();
        fieldBackground.beginFill('green');
        fieldBackground.drawRect(0, 0, this.FIELD_WIDTH, this.parentHeight * 0.8);
        fieldBackground.endFill();
        fieldBackground.x = (this.parentWidth / 2) - (this.FIELD_WIDTH / 2);
        fieldBackground.y = 0;
        this.fieldContainer.addChild(fieldBackground);

        // create and paint 10 lines to the right and left of the center line
        ['right', 'left'].forEach((side) => {
            for (let i = 0; i < 11; i++) {
                let line = new PIXI.Graphics();
                if (i % 2 === 0) {
                    line.lineStyle(2, 'white');
                } else {
                    line.lineStyle(2, 'grey');
                }
                line.moveTo(0, 0);
                line.lineTo(0, this.parentHeight * 0.8);
                side === 'right' ? line.x = this.DEADCENTER_H + (this.FIELD_WIDTH / 25 * i) :
                    line.x = this.DEADCENTER_H - (this.FIELD_WIDTH / 25 * i);
                if (i === 10) {
                    if (!this.goalLines) {
                        this.goalLines = new Map();
                    }
                    this.goalLines.set(side, line.x);
                }
                line.y = 0;
                this.fieldContainer.addChild(line);
            }
        });

        let positions = [
            // top line and bottom line
            {
                name: 'topLine',
                x: (this.parentWidth / 2) - (this.FIELD_WIDTH / 2),
                y: 0,
                width: this.FIELD_WIDTH,
                height: 0
            },
            {
                name: 'bottomLine',
                x: (this.parentWidth / 2) - (this.FIELD_WIDTH / 2),
                y: this.parentHeight * 0.8,
                width: this.FIELD_WIDTH,
                height: 0
            },
            // outer left line and outer right line
            {
                name: 'outerLeftLine',
                x: (this.parentWidth / 2) - (this.FIELD_WIDTH / 2),
                y: 0,
                width: 0,
                height: this.parentHeight * 0.8
            },
            {
                name: 'outerRightLine',
                x: (this.parentWidth / 2) + (this.FIELD_WIDTH / 2),
                y: 0,
                width: 0,
                height: this.parentHeight * 0.8
            },
            // center line
            {name: 'centerLine', x: this.DEADCENTER_H, y: 0, width: 0, height: this.parentHeight * 0.8},
        ];

        for (let i = 0; i < positions.length; i++) {
            let pos = positions[i];
            let line = this.createLine(pos.name, 'white', pos.x, pos.y, pos.width, pos.height);
            this.fieldContainer.addChild(line);
        }

        // add yard numbers under every second line (white ones)
        const yardNumbers = [50, 40, 30, 20, 10, 0];
        ['right', 'left'].forEach((side) => {
            for (let i = 0; i < 11; i += 2) {
                let yardNumber = new PIXI.Text((yardNumbers[i / 2]).toString(), {
                    fontFamily: 'Arial',
                    fontSize: 12,
                    fill: 'white',
                    align: 'center'
                });
                yardNumber.anchor.set(0.5);
                side === 'right' ? yardNumber.x = this.DEADCENTER_H + (this.FIELD_WIDTH / 25 * i) :
                    yardNumber.x = this.DEADCENTER_H - (this.FIELD_WIDTH / 25 * i);
                yardNumber.y = this.parentHeight * 0.8 + 15;
                this.fieldTextsContainer.addChild(yardNumber);
            }
        });

        this.app.stage.addChild(this.fieldContainer);
        this.app.stage.addChild(this.fieldTextsContainer);
        this.app.stage.addChild(this.footballLineContainer);
        this.app.stage.addChild(this.footballContainer);
    }

    createLine(name, color, x, y, width, height, opacity = 1, lineWidth = 2) {
        let line = new PIXI.Graphics();
        line.alpha = opacity;
        line.lineStyle(lineWidth, color);
        line.moveTo(0, 0);
        if (width > height) {
            line.lineTo(width, 0);
        } else {
            line.lineTo(0, height);
        }
        line.x = x;
        line.y = y;
        line.name = name;
        return line;
    }

    createCurve(name, startX, startY, width, opacity = 1) {
        startY -= this.lineNumber > 0 ? this.lineNumber * 5 : 5;
        console.log('startpoint', startX, startY, 'width', width);
        let curve = new PIXI.Graphics();
        curve.alpha = opacity;
        curve.lineStyle(4, 'white');
        curve.moveTo(0, 0);
        curve.quadraticCurveTo(width / 2, -50, width, 0);
        curve.x = startX;
        curve.y = startY;
        curve.name = name;
        return curve;
    }

    updateText(text) {
        this.app.stage.removeChild(this.gameText);
        this.app.stage.removeChild(this.extraText);
        // we have to texts, when there was a penalty, interception, etc.
        let texts = text.split(';');

        this.gameText = new PIXI.Text(texts[0], {
            fontFamily: 'Arial',
            fontSize: 14,
            fill: 'white',
            align: 'center',
            wordWrap: true,
            wordWrapWidth: this.FIELD_WIDTH
        });
        this.gameText.anchor.set(0.5);
        this.gameText.x = (this.parentWidth / 2);
        this.gameText.y = (this.parentHeight * 0.90);
        this.app.stage.addChild(this.gameText);

        if (texts.length > 1) {
            this.extraText = new PIXI.Text(texts[1], {
                fontFamily: 'Arial',
                fontSize: 14,
                fill: 'white',
                align: 'center',
                wordWrap: true,
                wordWrapWidth: this.FIELD_WIDTH
            });
            this.extraText.anchor.set(0.5);
            this.extraText.x = (this.parentWidth / 2);
            this.extraText.y = (this.parentHeight * 0.95);
            this.app.stage.addChild(this.extraText);
        }
    }

    createNewFootball() {
        let football = PIXI.Sprite.from(this.assetPipeline.football);
        football.name = 'football';
        football.width = 32;
        football.height = 32;
        football.x = this.actualFootballPosition;
        football.y = this.parentHeight * 0.8 / 2 - 16;
        return football;
    }

    updateFootball(move, direction, yardsToTD, isInitial) {
        // move is OffGameplay => 'Pass' or 'Run' with semicolon for the play
        console.log('move: ' + move + ', direction: ' + direction + ', yardsToTD: ' + yardsToTD);
        console.log('goalLines', this.goalLines);
        this.move = move;
        let oneYard = this.FIELD_WIDTH / 25 / 5;
        let yardsToTDInPixels = oneYard * yardsToTD;
        console.log('yardsToTDInPixels: ' + yardsToTDInPixels);
        console.log('midline: ' + this.DEADCENTER_H);

        if (direction === 'right') {
            this.endPositionAsPixelValue = this.goalLines.get('right') - yardsToTDInPixels;
        } else if (direction === 'left') {
            this.endPositionAsPixelValue = this.goalLines.get('left') + yardsToTDInPixels;
        }

        if (this.endPositionAsPixelValue > this.goalLines.get('right')) {
            this.endPositionAsPixelValue = this.goalLines.get('right');
        } else if (this.endPositionAsPixelValue < this.goalLines.get('left')) {
            this.endPositionAsPixelValue = this.goalLines.get('left');
        }

        console.log('endPositionAsPixelValue: ' + this.endPositionAsPixelValue);

        let footballPosition = this.footballContainer.getChildByName('football').x;
        console.log('footballPosition: ' + footballPosition);

        this.footballTicker = new PIXI.Ticker();
        this.footballTicker.add((delta) => {
            const actualPosition = Math.round(this.footballContainer.getChildByName('football').x);
            const endPosition = Math.round(this.endPositionAsPixelValue);
            if (actualPosition !== endPosition && !isInitial) {
                this.moveFootball(delta, endPosition);
            } else {
                console.log('finished moving football to ' + endPosition + ' from ' + footballPosition);
                this.actualFootballPosition = endPosition - 16;
                if (this.footballTicker) {
                    this.footballTicker.destroy();
                }
            }
        });
        this.footballTicker.start();

        if (this.move !== null) {
            console.log('add football line for ' + this.move);
            this.addFootballLine(footballPosition);
        } else {
            console.log('no move, delete all lines');
            this.footballLineContainer.removeChildren();
        }
    }

    addFootballLine(oldPosition) {
        const lineWidth = Math.abs(this.actualFootballPosition - oldPosition);
        this.lineNumber = !this.lineNumber ? 1 : ++this.lineNumber % 3;
        let oldFootballLine = this.footballLineContainer.getChildByName('footballLine' + (this.lineNumber));
        if (oldFootballLine) {
            this.footballLineContainer.removeChild(oldFootballLine);
        }
        let footballLineName = 'footballLine' + (this.lineNumber);
        let footballLineOpacity = 0.7;

        let footballLine;
        let move = this.move.split(';')[0];
        if (move === 'Run') {
            console.log('painting running line');
            let y = this.parentHeight * 0.8 / 2 - 16;
            y -= this.lineNumber > 0 ? this.lineNumber * 10 : 10;
            footballLine = this.createLine(footballLineName, 'white', oldPosition,
                y, lineWidth, 5, footballLineOpacity, 4);
        } else if (move === 'Pass') {
            console.log('painting passing curve');
            console.log('footballLineName: ' + footballLineName);
            console.log('oldPosition: ' + oldPosition);
            console.log('this.parentHeight * 0.8 / 2 - 16: ' + (this.parentHeight * 0.8 / 2 - 16));
            console.log('this.actualFootballPosition: ' + this.actualFootballPosition);
            console.log('this.parentHeight * 0.8 / 2 - 16: ' + (this.parentHeight * 0.8 / 2 - 16));
            console.log('lineWidth: ' + lineWidth);
            footballLine = this.createCurve(footballLineName, oldPosition,
                this.parentHeight * 0.8 / 2 - 16, lineWidth, footballLineOpacity);
        }

        if (oldPosition !== this.actualFootballPosition) {
            this.footballLineContainer.addChild(footballLine);
        }
    }

    moveFootball(delta, endPosition) {
        console.log('move football');
        const actualPosition = Math.round(this.footballContainer.getChildByName('football').x) - 16;
        // move football to new position
        if (actualPosition < endPosition) {
            this.footballContainer.getChildByName('football').x += 1;
        } else if (actualPosition > endPosition) {
            this.footballContainer.getChildByName('football').x -= 1;
        }

    }

    play() {
        if (!this.isStarted && startTime !== Number.MAX_SAFE_INTEGER) {
            console.log('Spiel startet um ' + startTime);
            // send request to calc gameplay
            if (moment().unix() >= startTime) {
                this.isStarted = true;
                console.log('Spiel von ' + startTime + ' startet um ' + moment().unix());
                this.start();
            } else {
                this.updateText('Das Spiel startet demnächst. Die Spieler wärmen sich gerade noch auf.');
            }
        } else if (this.isFinished) {
            this.updateText('Das Spiel ist bereits beendet.');
            this.playTicker.destroy();
        }
    }

    start() {
        console.log('start');
        if (!this.interval) {
            calc(true);
            // interval = setInterval(calc, 15000);
            this.interval = setInterval(calc, 10000);
        }
    }


    finish() {
        console.log('finish');
        this.isFinished = true;
        $('#playClock').html('Spielende!');
        $('#quarter').html('');
        clearInterval(this.interval);
    }
}

$(function () {
    // when opening the site read the start-time of the game
    $.ajax({
        type: 'POST',
        url: AJAX_LIVE_URL + 'getStartTime.php',
        dataType: 'JSON',
        success: function (data) {
            startTime = data.startTime;
        }
    });

    game = new Game();
    game.init();
    updateStandings();
})

function updateGame(playClock, quarter, down, isEnd, text, yardsToFirstDown, yardsToTD, offGameplay, direction,
                    left, right, leftTeamPart, rightTeamPart, isInitial) {
    if (isEnd) {
        game.finish();
    } else {
        $('#quarter').html(quarter);

        let minutes = Math.floor(playClock / 60);
        minutes = minutes < 10 ? '0' + minutes : minutes;
        let seconds = playClock - minutes * 60;
        seconds = seconds < 10 ? '0' + seconds : seconds;
        $('#playClock').html(minutes + ':' + seconds);

        if (yardsToTD <= 0) {
            $('#down').html('Touchdown!');
        } else {
            $('#down').html(down + ' & ' + yardsToFirstDown);
        }

        game.updateText(text);
        game.updateFootball(offGameplay, direction, yardsToTD, isInitial);
        loadTeams(left, right, leftTeamPart, rightTeamPart);
        updateStandings();
    }
}

async function calc(isInitial) {
    const response = await fetch((AJAX_LIVE_URL + 'getCalcResult.php'), {
        method: 'POST',
        body: JSON.stringify({
            gameTime: moment().unix()
        })
    });

    if (!response.ok) {
        console.log('Network response was not ok. Status: ' + response.status + ' ' + response.statusText);
        game.updateText('Error um ' + new Date().toTimeString() + '. Gerne an Jannik weitergeben.');
        return;
    }

    const data = await response.json();
    console.log(data);

    let quarterText;
    const {
        playClock, quarter, down, isEnd, gametext, yardsToFirstDown, yardsToTD, offGameplay, defGameplay, direction,
        left, right, leftTeamPart, rightTeamPart, runner, secondRB
    } = data;

    let text = 'GameText: ' + gametext;
    if (runner) {
        text += '\nRunner: ' + runner;
        text += '\nsecondRB: ' + (secondRB || false);
    }
    if (offGameplay) {
        text += '\nOff-Gameplay: ' + offGameplay;
    }
    if (defGameplay) {
        text += '\nDef-Gameplay: ' + defGameplay;
    }
    if (yardsToTD) {
        text += '\nYardsToTD: ' + yardsToTD;
    }

    switch (quarter) {
        case 1:
            quarterText = '1st';
            break;
        case 2:
            quarterText = '2nd';
            break;
        case 3:
            quarterText = '3rd';
            break;
        case 4:
            quarterText = '4th';
            break;
        case 5:
            quarterText = 'OT';
            break;
        default:
            quarterText = quarter;
            break;
    }

    updateGame(
        playClock,
        quarterText,
        down,
        isEnd,
        text,
        yardsToFirstDown,
        yardsToTD,
        offGameplay,
        direction,
        left,
        right,
        leftTeamPart,
        rightTeamPart,
        isInitial
    );
}


async function updateStandings() {
    await fetch(AJAX_LIVE_URL + 'getStandings.php', {method: 'GET'})
        .then(response => response.json())
        .then(data => {
            if (null != data.standings) {
                TBL_STANDINGS.bootstrapTable();
                TBL_STANDINGS.bootstrapTable('load', data.standings);
            }
        });
}

async function loadTeams(home, away, homeTeamPart, awayTeamPart) {
    const [homeTeamResponse, awayTeamResponse] = await Promise.all([
        fetch(AJAX_LIVE_URL + 'getStartingEleven.php', {
            method: 'POST',
            body: JSON.stringify({
                teamName: home,
                teamPart: homeTeamPart
            })
        }),
        fetch(AJAX_LIVE_URL + 'getStartingEleven.php', {
            method: 'POST',
            body: JSON.stringify({
                teamName: away,
                teamPart: awayTeamPart
            })
        })
    ]);

    const [homeTeam, awayTeam] = await Promise.all([
        homeTeamResponse.text(),
        awayTeamResponse.text()
    ]);

    $('#colHome').show().html(homeTeam);
    $('#colAway').show().html(awayTeam);
    game.resize();
}
