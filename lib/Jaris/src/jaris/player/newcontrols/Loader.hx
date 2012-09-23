﻿/**    
 * @author Jefferson González
 * @copyright 2010 Jefferson González
 *
 * @license 
 * This file is part of Jaris FLV Player.
 *
 * Jaris FLV Player is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License or GNU LESSER GENERAL 
 * PUBLIC LICENSE as published by the Free Software Foundation, either version 
 * 3 of the License, or (at your option) any later version.
 *
 * Jaris FLV Player is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License and 
 * GNU LESSER GENERAL PUBLIC LICENSE along with Jaris FLV Player.  If not, 
 * see <http://www.gnu.org/licenses/>.
 */

package jaris.player.newcontrols;

import flash.display.MovieClip;
import flash.display.Sprite;
import flash.display.Stage;
import flash.events.Event;
import flash.Lib;
import flash.geom.Matrix;
import jaris.utils.Utils;
import flash.display.GradientType;

/**
 * Draws a loading bar
 */
class Loader extends Sprite 
{
	private var _stage:Stage;
	private var _movieClip:MovieClip;
	private var _background:Sprite;
	private var _loaderTrack:Sprite;
	private var _loaderThumb:Sprite;
	private var _visible:Bool;
	private var _darkColor:UInt;
	private var _controlColor:UInt;
	private var _seekColor:UInt;
	private var _forward:Bool;

	public function new() 
	{
		super();
		
		_stage = Lib.current.stage;
		_movieClip = Lib.current;
		
		_background = new Sprite();
		addChild(_background);
		
		_loaderTrack = new Sprite();
		addChild(_loaderTrack);
		
		_loaderThumb = new Sprite();
		addChild(_loaderThumb);
		
		_darkColor = 0x000000;
		_controlColor = 0xFFFFFF;
		_seekColor = 0x747474;
		
		_forward = true;
		_visible = true;
		
		addEventListener(Event.ENTER_FRAME, onEnterFrame);
		_stage.addEventListener(Event.RESIZE, onResize);
		
		drawLoader();
	}
	
	/**
	 * Animation of a thumb moving on the track
	 * @param	event
	 */
	private function onEnterFrame(event:Event):Void
	{
		if (_visible)
		{
			if (_forward)
			{
				if ((_loaderThumb.x + _loaderThumb.width) >= (_loaderTrack.x + _loaderTrack.width))
				{
					_forward = false;
				}
				else
				{
					_loaderThumb.x += 10;
				}
			}
			else
			{
				if (_loaderThumb.x  <= _loaderTrack.x)
				{
					_forward = true;
				}
				else
				{
					_loaderThumb.x -= 10;
				}
			}
		}
	}
	
	/**
	 * Redraws the loader to match new stage size
	 * @param	event
	 */
	private function onResize(event:Event):Void
	{
		drawLoader();
	}
	
	/**
	 * Draw loader graphics
	 */
	private function drawLoader():Void
	{
		//Clear graphics
		_background.graphics.clear();
		_loaderTrack.graphics.clear();
		_loaderThumb.graphics.clear();
		
		//Draw background
		var backgroundWidth:Float = (65 / 100) * _stage.stageWidth;
		var backgroundHeight:Float = 30;
		_background.x = (_stage.stageWidth / 2) - (backgroundWidth / 2);
		_background.y = (_stage.stageHeight / 2) - (backgroundHeight / 2);
		_background.graphics.lineStyle();
		_background.graphics.beginFill(_darkColor, 0.75);
		_background.graphics.drawRoundRect(0, 0, backgroundWidth, backgroundHeight, 6, 6);
		_background.graphics.endFill();
		
		//Draw track
		var trackWidth:Float = (50 / 100) * _stage.stageWidth;
		var trackHeight:Float = 11;
		_loaderTrack.x = (_stage.stageWidth / 2) - (trackWidth / 2);
		_loaderTrack.y = (_stage.stageHeight / 2) - (trackHeight / 2);
		_loaderTrack.graphics.lineStyle();
		_loaderTrack.graphics.beginFill(_seekColor, 0.3);
		_loaderTrack.graphics.drawRoundRect(0, trackHeight/2/2, trackWidth, trackHeight/2, 5, 5);
		
		//Draw thumb
		var matrix:Matrix = new Matrix(  );
		matrix.createGradientBox(trackHeight*3, trackHeight, Utils.degreesToRadians(-90), trackHeight*3, 0);
		var colors:Array<UInt> = [_controlColor, _controlColor];
		var alphas:Array<Float> = [0.75, 1];
		var ratios:Array<UInt> = [0, 255];
		
		_loaderThumb.x = _loaderTrack.x;
		_loaderThumb.y = _loaderTrack.y;
		_loaderThumb.graphics.lineStyle();
		_loaderThumb.graphics.beginGradientFill(GradientType.LINEAR, colors, alphas, ratios, matrix);
		//_loaderThumb.graphics.beginFill(_controlColor, 1);
		_loaderThumb.graphics.drawRoundRect(0, 0, trackHeight*3, trackHeight, 10, 10);
	}
	
	/**
	 * Stops drawing the loader
	 */
	public function hide():Void
	{
		this.visible = false;
		_visible = false;
	}
	
	/**
	 * Starts drawing the loader
	 */
	public function show():Void
	{
		this.visible = true;
		_visible = true;
	}
	
	/**
	 * Set loader colors
	 * @param	colors
	 */
	public function setColors(colors:Array<String>):Void
	{
		_darkColor = colors[0].length > 0? Std.parseInt("0x" + colors[0]) : 0x000000;
		_controlColor = colors[1].length > 0? Std.parseInt("0x" + colors[1]) : 0xFFFFFF;
		_seekColor = colors[2].length > 0? Std.parseInt("0x" + colors[2]) : 0x747474;
		
		drawLoader();
	}
}