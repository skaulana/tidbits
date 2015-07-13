//
//   MISC.CPP
//
//   Miscellaneous functions for use with our ray tracer.
//

#ifndef _MISC_CPP
#define _MISC_CPP

#include <iostream>
#include <fstream>
#include <vector>
#include <cmath>

#include "framework.h"

using namespace std;

extern float EPSILON;

//
//   Modified .scene format:
//
//   Camera
//   - eye position (3 floats)
//   - ul, ur, ll, lr corners of image plane (3 floats x4)
//     For each transform
//     - translate to x y z (3 floats)
//     - scale in x y z (3 floats)
//     - rotate by degrees on axis x y z (4 floats)
//
//   # of objects in scene
//   For each object
//   - diffuse color (3 floats)
//   - ka, kd, ks, krefl (4 floats)
//     For each transform
//     - translate to x y z (3 floats)
//     - scale in x y z (3 floats)
//     - rotate by degrees on axis x y z (4 floats)
//   - object.obj
//
//   # of lights in scene
//   For each light
//   - ambient color + alpha (4 floats)
//   - diffuse color + alpha (4 floats)
//   - specular color + alpha (4 floats)
//   - position in x y z w (4 floats)
//

void parseInputFile(char *fname, RaySource &origin, vector<Object> &objects, vector<Light> &lights)
{
	ifstream in(fname, ios::in);

	Object obj; Transform t; char str[80];
	int numObjects, numLights, i;

	if (!in.good())
	{
		cerr << "Unable to open \"" << fname << "\" for output." << endl;
		exit(1);
	}

	//
	//   For our camera, all we need now is a position and plane with transformations.
	//

	in >> origin.pos[0] >> origin.pos[1] >> origin.pos[2];
	in >> origin.ul[0] >> origin.ul[1] >> origin.ul[2];
	in >> origin.ur[0] >> origin.ur[1] >> origin.ur[2];
	in >> origin.ll[0] >> origin.ll[1] >> origin.ll[2];
	in >> origin.lr[0] >> origin.lr[1] >> origin.lr[2];

	int done = 0;

	while (!done)
	{
		in >> str;
		if (strstr(str, "translate"))
		{
			t.type = Transform::TRANSLATE;
			in >> t.translate[0] >> t.translate[1] >> t.translate[2];
			t.applyTo(origin);
		}
		else if (strstr(str, "rotate"))
		{
			t.type = Transform::ROTATE;
			in >> t.angle >> t.axis[0] >> t.axis[1] >> t.axis[2];
			t.angle *= M_PI / 180.0f; normalize(t.axis);
			t.applyTo(origin);
		}
		else if (strstr(str, "scale"))
		{
			t.type = Transform::SCALE;
			in >> t.scale[0] >> t.scale[1] >> t.scale[2];
			t.applyTo(origin);
		}
		else
		{
			numObjects = atoi(str); done = 1;
		}
	}

	//
	//   Objects are handled as in our previous walkthrough.
	//

	objects.resize(numObjects);
	
	for (i = 0; i < numObjects; i++)
	{
		objects[i].id = i + 1;
		objects[i].normalsComputed = false;

		in >> objects[i].color[0] >> objects[i].color[1] >> objects[i].color[2];
		in >> objects[i].ka >> objects[i].kd >> objects[i].ks >> objects[i].krefl;

		done = 0;

		//
		//   Read transformations, then the object files, then compute normals.
		//

		while (!done)
		{
			in >> str;
			if (strstr(str, "translate"))
			{
				t.type = Transform::TRANSLATE;
				in >> t.translate[0] >> t.translate[1] >> t.translate[2];
				objects[i].transforms.push_back(t);
			}
			else if (strstr(str, "rotate"))
			{
				t.type = Transform::ROTATE;
				in >> t.angle >> t.axis[0] >> t.axis[1] >> t.axis[2];
				t.angle *= M_PI / 180.0f; normalize(t.axis);
				objects[i].transforms.push_back(t);
			}
			else if (strstr(str, "scale"))
			{
				t.type = Transform::SCALE;
				in >> t.scale[0] >> t.scale[1] >> t.scale[2];
				objects[i].transforms.push_back(t);
			}
			else
			{
				objects[i].readObject(str); done = 1;
			}
		}

		objects[i].computeFaceNormals();
	}

	//
	//   Read the lights in the scene.
	//

	in >> numLights; lights.resize(numLights);
	
	for (i = 0; i < numLights; i++)
	{
		Light &l = lights[i]; l.id = i + 1;

		in >> l.ambient[0] >> l.ambient[1] >> l.ambient[2];
		in >> l.diffuse[0] >> l.diffuse[1] >> l.diffuse[2];
		in >> l.specular[0] >> l.specular[1] >> l.specular[2];

		in >> str;
		if (strstr(str, "dir"))
		{
			l.type = Light::DIRECTIONAL;
			in >> l.dir[0] >> l.dir[1] >> l.dir[2];
		}
		else
		{
			l.type = Light::POINT;
			in >> l.pos[0] >> l.pos[1] >> l.pos[2];
		}
	}
}

//
//   Less-than-picky comparison operators.
//

bool mostlyEqual(float f, float g) { return float(fabs(f-g)) <= EPSILON; }
bool mostlyEqual(SmVector3 s, SmVector3 t) { return mostlyEqual(s[0],t[0]) && mostlyEqual(s[1],t[1]) && mostlyEqual(s[2],t[2]); }

#endif
