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

package jaris.player.controls;
import flash.display.Sprite;
import flash.events.MouseEvent;

class FullscreenIcon extends Sprite
{
	private var _width:Float;
	private var _height:Float;
	private var _normalColor:UInt;
	private var _hoverColor:UInt;
	
	public function new(x:Float, y:Float, width:Float, height:Float, normalColor:UInt, hoverColor:UInt) 
	{
		super();
		
		this.x = x;
		this.y = y;
		this.buttonMode = true;
		this.useHandCursor = true;
		this.tabEnabled = false;
		
		_width = width;
		_height = height;
		_normalColor = normalColor;
		_hoverColor = hoverColor;
		
		addEventListener(MouseEvent.MOUSE_OVER, onMouseOver);
		addEventListener(MouseEvent.MOUSE_OUT, onMouseOut);
		
		draw(_normalColor);
	}
	
	private function onMouseOver(event:MouseEvent):Void
	{
		draw(_hoverColor);
	}
	
	private function onMouseOut(event:MouseEvent):Void
	{
		draw(_normalColor);
	}
	
	//{Private Methods
	private function draw(color:UInt):Void
	{
		graphics.clear();
		
		graphics.lineStyle(2, color);
		graphics.beginFill(0x000000, 0);
		graphics.drawRoundRect(0, 0, _width, _height, 6, 6);
		graphics.endFill();
		
		graphics.lineStyle();
		graphics.beginFill(color, 1);
		graphics.drawRoundRect(3, 3, 4, 4, 2, 2);
		graphics.drawRoundRect(width - 9, 3, 4, 4, 2, 2);
		graphics.drawRoundRect(3, height - 9, 4, 4, 2, 2);
		graphics.drawRoundRect(width - 9, height - 9, 4, 4, 2, 2);
		graphics.endFill();
	}
	//}
	
	//{Setters
	public function setNormalColor(color:UInt):Void
	{
		_normalColor = color;
		draw(_normalColor);
	}
	
	public function setHoverColor(color:UInt):Void
	{
		_hoverColor = color;
		draw(_hoverColor);
	}
	
	public function setPosition(x:Float, y:Float):Void
	{
		this.x = x;
		this.y = y;
		
		draw(_normalColor);
	}
	
	public function setSize(width:Float, height:Float):Void
	{
		_width = width;
		_height = height;
		
		draw(_normalColor);
	}
	//}
}