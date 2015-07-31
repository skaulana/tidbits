// HW 5 - Simple Paint - implementation (cpp)

#include "Win5.h"
#include <windows.h>

LRESULT CALLBACK myWinProc(HWND, UINT, WPARAM, LPARAM);

HINSTANCE hInst;
static char appname[] = "HW 5 - Simple Paint";

int WINAPI WinMain (HINSTANCE hCurrentInstance, HINSTANCE hPreviousInstance,
					LPSTR lpszCmdLine, int nCmdShow)
{
	HWND hwnd;
	MSG message;
	WNDCLASSEX wc;
	
	// fill in window class
	wc.style		= CS_HREDRAW | CS_VREDRAW;
	wc.lpfnWndProc	= myWinProc;
	wc.cbClsExtra	= 0;
	wc.cbWndExtra	= 0;
	wc.hInstance	= hCurrentInstance;
	wc.hIcon		= LoadIcon(0, IDI_APPLICATION);
	wc.hIconSm		= LoadIcon(0, IDI_APPLICATION);
	wc.hCursor		= LoadCursor(0, IDC_ARROW);
	wc.hbrBackground	= HBRUSH(GetStockObject(WHITE_BRUSH));
	wc.lpszMenuName		= "StaticMenu";
	wc.lpszClassName	= appname;
	wc.cbSize			= sizeof(WNDCLASSEX);
	
	hInst = hCurrentInstance;
	
	if(!RegisterClassEx(&wc)) {
		MessageBox(NULL,"Error in WNDCLASS", "Error Message", MB_ICONSTOP);
		return(FALSE);
	}
	
	hwnd = CreateWindow(appname, "HW 5 - Simple Paint",
		WS_OVERLAPPEDWINDOW,
		CW_USEDEFAULT, CW_USEDEFAULT,
		CW_USEDEFAULT, CW_USEDEFAULT,
		0,0, hCurrentInstance, 0);
		
	if (!hwnd) {
		MessageBox(NULL, "Failed to create top-level window",
					"Error Message", MB_ICONSTOP);
		PostQuitMessage(1);
	}
	
	ShowWindow(hwnd, nCmdShow);
	UpdateWindow(hwnd);
	
	// Message loop
	while (GetMessage(&message, NULL, NULL, NULL))
	{
		TranslateMessage(&message);
		DispatchMessage(&message);
	}
	return message.wParam;
}

// handle the various Win32 messages

LRESULT CALLBACK myWinProc(HWND hWnd, UINT message,
	WPARAM wParam, LPARAM lParam)
{
	HDC hdc;
	PAINTSTRUCT ps;
	static HMENU hMenu;
	static int pRGB[3];
	static int pWidth, pStyle;
	static int bColor[5] = {
		WHITE_BRUSH, LTGRAY_BRUSH, GRAY_BRUSH, DKGRAY_BRUSH, BLACK_BRUSH};
	static POINT pt, ptprev, ptnext;
	bool drawFlag = true;
	
	switch (message)
	{
		case WM_CREATE:
			hMenu = LoadMenu(hInst, "PopMenu");
			hMenu = GetSubMenu(hMenu, 0);
			pRGB[0] = 0;
			pRGB[1] = 0;
			pRGB[2] = 0;
			pWidth = IDM_PW1 - 20;
			pStyle = IDM_PSOLID;
			ptprev.x = 0;
			ptprev.y = 0;
			ptnext.x = 0;
			ptnext.y = 0;			
			return 0;
		
		case WM_LBUTTONDOWN:
			ptnext.x = LOWORD(lParam);
			ptnext.y = HIWORD(lParam);
			InvalidateRect(hWnd, 0, false);
			return 0;
		
		case WM_RBUTTONDOWN:
			pt.x = LOWORD(lParam);
			pt.y = HIWORD(lParam);
			ClientToScreen(hWnd, &pt);
		
			TrackPopupMenu (hMenu, 0, pt.x, pt.y, 0, hWnd, 0);
			return 0;
		
		case WM_PAINT:
			hdc = BeginPaint(hWnd, &ps);
			
			if (pStyle == IDM_PDASH)
				SelectObject(hdc,CreatePen(PS_DASH, 0, RGB(pRGB[0],pRGB[1],pRGB[2])));
			else if (pStyle == IDM_PDOT)
				SelectObject(hdc, CreatePen(PS_DOT, 0, RGB(pRGB[0],pRGB[1],pRGB[2])));
			else if (pStyle == IDM_PDASHDOT)
				SelectObject(hdc, CreatePen(PS_DASHDOT, 0, RGB(pRGB[0],pRGB[1],pRGB[2])));
			else if (pStyle == IDM_PDASHDOTDOT)
				SelectObject(hdc, CreatePen(PS_DASHDOTDOT, 0, RGB(pRGB[0],pRGB[1],pRGB[2])));
			else SelectObject(hdc, CreatePen(PS_SOLID, pWidth, RGB(pRGB[0],pRGB[1],pRGB[2])));
				
			if (drawFlag) {
				MoveToEx (hdc, ptprev.x, ptprev.y, 0);
				LineTo (hdc, ptnext.x, ptnext.y);
			}
				
			ptprev.x = ptnext.x;
			ptprev.y = ptnext.y;
			DeleteObject(SelectObject(hdc, GetStockObject(BLACK_PEN)));
			
			drawFlag = true;
			return 0;
		
		case WM_COMMAND:
		{
			switch (LOWORD(wParam))
			{
				case IDM_EXIT:
					SendMessage(hWnd, WM_DESTROY, 0, 0);
					break;
				
				case IDM_PBLACK:
					pRGB[0] = 0; pRGB[1] = 0; pRGB[2] = 0; break;
				case IDM_PYELLOW:
					pRGB[0] = 255; pRGB[1] = 255; pRGB[2] = 0; break;
				case IDM_PRED:
					pRGB[0] = 255; pRGB[1] = 0; pRGB[2] = 0; break;
				case IDM_PGREEN:
					pRGB[0] = 0; pRGB[1] = 255; pRGB[2] = 0; break;
				case IDM_PBLUE:
					pRGB[0] = 0; pRGB[1] = 0; pRGB[2] = 255; break;
				
				case IDM_PW1:
				case IDM_PW2:
				case IDM_PW3:
				case IDM_PW4:
				case IDM_PW5:
					pWidth = LOWORD(wParam) - 20;
					break;
				
				case IDM_PSOLID:
				case IDM_PDASH:
				case IDM_PDOT:
				case IDM_PDASHDOT:
				case IDM_PDASHDOTDOT:
					pStyle = LOWORD(wParam);
					break;
				
				case IDM_BWHITE:
				case IDM_BLTGRAY:
				case IDM_BGRAY:
				case IDM_BDKGRAY:
				case IDM_BBLACK:
					
					SetClassLong(hWnd, GCL_HBRBACKGROUND, (LONG)GetStockObject(
							bColor[LOWORD(wParam) - IDM_BWHITE]));
					
					drawFlag = false;
					InvalidateRect(hWnd, 0, true);
					break;
				
				case IDM_ABOUT:
					MessageBox(hWnd, "HW 5 - Simple Paint\n\nA line painting program. Point and click to draw.\nProgram includes top level and pop-up menus.\n",
						"About...", MB_ICONINFORMATION | MB_OK);
					return 0;
			}
			return 0;
		}
		
		case WM_DESTROY:
			PostQuitMessage(0);
			return 0;

	}
	
	return DefWindowProc(hWnd, message, wParam, lParam);
	
}