<?php


namespace touchdownstars\statistics;

use Lombok\Getter;
use Lombok\Helper;
use Lombok\Setter;

/**
 * class StatisticsPlayer
 * @package touchdownstars\statistics
 * @method int getId()
 * @method void setId(int $id)
 * @method int getSeason()
 * @method void setSeason(int $season)
 * @method int getGameId()
 * @method void setGameId(int $gameId)
 * @method int getPaCpl()
 * @method void setPaCpl(int $paCpl)
 * @method int getPaAtt()
 * @method void setPaAtt(int $paAtt)
 * @method float getPaPct()
 * @method void setPaPct(float $paPct)
 * @method int getPaYds()
 * @method void setPaYds(int $paYds)
 * @method float getPaAvg()
 * @method void setPaAvg(float $paAvg)
 * @method int getPaTd()
 * @method void setPaTd(int $paTd)
 * @method int getPaInt()
 * @method void setPaInt(int $paInt)
 * @method int getSck()
 * @method void setSck(int $sck)
 * @method float getPaRtg()
 * @method void setPaRtg(float $paRtg)
 * @method int getRuAtt()
 * @method void setRuAtt(int $ruAtt)
 * @method int getRuYds()
 * @method void setRuYds(int $ruYds)
 * @method float getRuAvg()
 * @method void setRuAvg(float $ruAvg)
 * @method int getRuTd()
 * @method void setRuTd(int $ruTd)
 * @method int getFum()
 * @method void setFum(int $fum)
 * @method int getRec()
 * @method void setRec(int $rec)
 * @method int getReYds()
 * @method void setReYds(int $reYds)
 * @method float getReAvg()
 * @method void setReAvg(float $reAvg)
 * @method int getReTd()
 * @method void setReTd(int $reTd)
 * @method int getReYdsAC()
 * @method void setReYdsAC(int $reYdsAC)
 * @method float getReYdsACAvg()
 * @method void setReYdsACAvg(float $reYdsACAvg)
 * @method int getOvrTd()
 * @method void setOvrTd(int $ovrTd)
 * @method int getSckA()
 * @method void setSckA(int $sckA)
 * @method int getTkl()
 * @method void setTkl(int $tkl)
 * @method int getTfl()
 * @method void setTfl(int $tfl)
 * @method int getTflYds()
 * @method void setTflYds(int $tflYds)
 * @method int getSckMade()
 * @method void setSckMade(int $sckMade)
 * @method int getSckYds()
 * @method void setSckYds(int $sckYds)
 * @method float getDefAvg()
 * @method void setDefAvg(float $defAvg)
 * @method int getSft()
 * @method void setSft(int $sft)
 * @method int getDefl()
 * @method void setDefl(int $defl)
 * @method int getFf()
 * @method void setFf(int $ff)
 * @method int getIntcept()
 * @method void setIntcept(int $intcept)
 * @method int getIntYds()
 * @method void setIntYds(int $intYds)
 * @method int getIntTd()
 * @method void setIntTd(int $intTd)
 * @method int getFumRec()
 * @method void setFumRec(int $fumRec)
 * @method int getFumYds()
 * @method void setFumYds(int $fumYds)
 * @method int getFumTd()
 * @method void setFumTd(int $fumTd)
 * @method int getFgAtt()
 * @method void setFgAtt(int $fgAtt)
 * @method int getFgMade()
 * @method void setFgMade(int $fgMade)
 * @method int getFgLong()
 * @method void setFgLong(int $fgLong)
 * @method int getXpAtt()
 * @method void setXpAtt(int $xpAtt)
 * @method int getXpMade()
 * @method void setXpMade(int $xpMade)
 * @method int getPuntAtt()
 * @method void setPuntAtt(int $puntAtt)
 * @method int getPuntYds()
 * @method void setPuntYds(int $puntYds)
 * @method float getPuntAvg()
 * @method void setPuntAvg(float $puntAvg)
 * @method int getKrAtt()
 * @method void setKrAtt(int $krAtt)
 * @method int getKrYds()
 * @method void setKrYds(int $krYds)
 * @method float getKrAvg()
 * @method void setKrAvg(float $krAvg)
 * @method int getKrTd()
 * @method void setKrTd(int $krTd)
 * @method int getPrAtt()
 * @method void setPrAtt(int $prAtt)
 * @method int getPrYds()
 * @method void setPrYds(int $prYds)
 * @method float getPrAvg()
 * @method void setPrAvg(float $prAvg)
 * @method int getPrTd()
 * @method void setPrTd(int $prTd)
 * @method int getPenalty()
 * @method void setPenalty(int $penalty)
 * @method int getPenaltyYds()
 * @method void setPenaltyYds(int $penaltyYds)
 * @method int getIdPlayer()
 * @method void setIdPlayer(int $idPlayer)
 */
#[Setter, Getter]
class StatisticsPlayer extends Helper
{

    private int $id = 0;
    private int $season = 0;
    private int $gameId = 0;
    private int $paCpl = 0;
    private int $paAtt = 0;
    private float $paPct = 0.0;
    private int $paYds = 0;
    private float $paAvg = 0.0;
    private int $paTd = 0;
    private int $paInt = 0;
    private int $sck = 0;
    private float $paRtg = 0.0;
    private int $ruAtt = 0;
    private int $ruYds = 0;
    private float $ruAvg = 0.0;
    private int $ruTd = 0;
    private int $fum = 0;
    private int $rec = 0;
    private int $reYds = 0;
    private float $reAvg = 0.0;
    private int $reTd = 0;
    private int $reYdsAC = 0;
    private float $reYdsACAvg = 0.0;
    private int $ovrTd = 0;
    private int $sckA = 0;
    private int $tkl = 0;
    private int $tfl = 0;
    private int $tflYds = 0;
    private int $sckMade = 0;
    private int $sckYds = 0;
    private float $defAvg = 0.0;
    private int $sft = 0;
    private int $defl = 0;
    private int $ff = 0;
    private int $intcept = 0;
    private int $intYds = 0;
    private int $intTd = 0;
    private int $fumRec = 0;
    private int $fumYds = 0;
    private int $fumTd = 0;
    private int $fgAtt = 0;
    private int $fgMade = 0;
    private int $fgLong = 0;
    private int $xpAtt = 0;
    private int $xpMade = 0;
    private int $puntAtt = 0;
    private int $puntYds = 0;
    private float $puntAvg = 0.0;
    private int $krAtt = 0;
    private int $krYds = 0;
    private float $krAvg = 0.0;
    private int $krTd = 0;
    private int $prAtt = 0;
    private int $prYds = 0;
    private float $prAvg = 0.0;
    private int $prTd = 0;
    private int $penalty = 0;
    private int $penaltyYds = 0;
    private int $idPlayer;
}