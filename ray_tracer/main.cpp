//
//   MAIN.CPP
//
//   Ray tracer for the fourth assignment.
//

#include <iostream>
#include <fstream>
#include <vector>
#include <cmath>

#include "framework.h"
#include "misc.cpp"

using namespace std;

//
//   Some global variables.
//

bool DEBUG = false, BOUNDING_BOX = true, ANTI_ALIAS = false; int RECURSION_DEPTH = 3;
float MAX_DIST = 1000.0f, EPSILON = 0.000001f, PHONG_EXPONENT = 16.0f;

SmVector3 BG_COLOR(0.0f, 0.0f, 0.0f);

vector<Object> objects;
vector<Light> lights;
RaySource origin;

//
//   Main function prototypes.
//

SmVector3 getRayColor(Ray &, int); // color returned in range of 0.0 - 1.0

float getRayIntersection(Ray &, Primitive **, SmVector3 &, float, bool, int);
float getRayNearestIntersection(Ray &, Primitive **, SmVector3 &);
float getRayBoundedIntersection(Ray &, float, int);

//
//   The ray tracing program.
//

int main(int argc, char *argv[])
{
	int width = 800;
	int height = 600;
	int jitter = 4;

	char *infile = NULL;
	char *outfile = NULL;
	char *defaultinfile = "default.scene";
	char *defaultoutfile = "out.ppm";

	srand(time(NULL));

	//
	//   Accept input parameters.
	//

	if (argc > 1 && strstr(argv[1], ".scene"))       { infile = new char[strlen(argv[1]) + 1]; strcpy(infile, argv[1]); }

	for(int i = 0; i < argc; i++)
	{
		if (!strcmp(argv[i], "-f"))
		{
			if (argc <= i+1)                 { cerr << "You must specify an input file argument." << endl; return 1; }
			else                             { infile = new char[strlen(argv[i+1]) + 1]; strcpy(infile, argv[i+1]); }
		}
		else if (!strcmp(argv[i], "-o"))
		{
			if (argc <= i+1)                 { cerr << "You must specify an output file argument." << endl; return 1; }
			else                             { outfile = new char[strlen(argv[i+1]) + 1]; strcpy(outfile, argv[i+1]); }
		}
		else if (!strcmp(argv[i], "-w"))
		{
			if (argc <= i+1)                 { cerr << "You must specify an image width argument." << endl; return 1; }
			else if (strstr(argv[i+1], "-")) { cerr << "Invalid image width argument." << endl; return 1; }
			else                             { width = atoi(argv[i+1]); }
		}
		else if (!strcmp(argv[i], "-h"))
		{
			if (argc <= i+1)                 { cerr << "You must specify an image height argument." << endl; return 1; }
			else if (strstr(argv[i+1], "-")) { cerr << "Invalid image height argument." << endl; return 1; }
			else                             { height = atoi(argv[i+1]); }
		}
		else if (!strcmp(argv[i], "-bb") || !strcmp(argv[i], "-b"))
		{
			if (argc <= i+1 || !(strstr(argv[i+1], "on") || strstr(argv[i+1], "off")))
			{
				cerr << "You must specify \"on\" or \"off\" for the bounding box argument." << endl; return 1;
			}
			else BOUNDING_BOX = strstr(argv[i+1], "on");
		}
		else if (!strcmp(argv[i], "-aa") || !strcmp(argv[i], "-a"))
		{
			if (argc <= i+1 || !(strstr(argv[i+1], "on") || strstr(argv[i+1], "off")))
			{
				cerr << "You must specify \"on\" or \"off\" for the anti-aliasing argument." << endl; return 1;
			}
			else ANTI_ALIAS = strstr(argv[i+1], "on");
		}
		else if (!strcmp(argv[i], "--debug"))
		{
			DEBUG = true; width = 320; height = 240;
			infile = infile == NULL ? defaultinfile : infile;
			outfile = outfile == NULL ? defaultoutfile : outfile;
		}
	}

	if (outfile == NULL) outfile = defaultoutfile;
	if (infile == NULL)
	{
		cout << "Usage: raytrace [-f] input.scene [-o out.ppm] [-w #] [-h #] [-bb on/off] [-aa on/off]" << endl;
		return 0;
	}

	if (DEBUG) cout << "Starting in debug mode..." << endl;

	ofstream os(outfile, ios::out); if (!os.good())
	{
		cerr << "Unable to open \"" << outfile << "\" for output." << endl;
		exit(1);
	}
	os.close();

	if (DEBUG)
	{
		cout << "   Image dimensions " << width << " x " << height << " pixels..." << endl;
		cout << "   Bounding boxes are " << (BOUNDING_BOX ? "enabled" : "disabled") << "..." << endl;
		cout << "   Anti-aliasing is " << (ANTI_ALIAS ? "enabled" : "disabled") << "..." << endl;
	}

	//
	//   Prepare for rendering.
	//

	unsigned char *image_buffer_red   = new unsigned char[width * height];
	unsigned char *image_buffer_green = new unsigned char[width * height];
	unsigned char *image_buffer_blue  = new unsigned char[width * height];

	for(int i = 0; i < width * height; i++)
	{
		image_buffer_red[i] = 0; image_buffer_green[i] = 0; image_buffer_blue[i] = 0;
	}

	parseInputFile(infile, origin, objects, lights);

	origin.width = width; origin.height = height;

	//
	//   Trace rays.
	//

	cout << "Ray tracing" << (DEBUG ? "" : ", please wait") << "..." << endl;

	for(int y = 0; y < height; y++)
	{
		if (DEBUG)
		{
			for(float percent = 0.1f; percent < 1.0f; percent += 0.1f)
				if (mostlyEqual(float(y)/float(height), percent))
					cout << "   " << (percent * 100) << "% completed..." << endl;
		}

		for(int x = 0; x < width; x++)
		{
			SmVector3 c = BG_COLOR;

			if (!ANTI_ALIAS)
			{
				Ray r = origin.getPixelRay(x, y);

				c += getRayColor(r, RECURSION_DEPTH);
			}
			else
			{
				SmVector3 avg = 0.0f;

				for(int j = 0; j < jitter; j++)
				{
					for(int i = 0; i < jitter; i++)
					{
						Ray r = origin.getJitteredPixelRay(x, y, i, j, jitter);

						avg += getRayColor(r, RECURSION_DEPTH);
					}
				}

				avg /= float(jitter) * float(jitter); c += avg;
			}

			if (c[0] > 1.0f) c[0] = 1.0f; // no brighter than white
			if (c[1] > 1.0f) c[1] = 1.0f;
			if (c[2] > 1.0f) c[2] = 1.0f;

			image_buffer_red  [y*width + x] = int(c[0] * 255); // integer op will truncate
			image_buffer_green[y*width + x] = int(c[1] * 255);
			image_buffer_blue [y*width + x] = int(c[2] * 255);
		}
	}

	//
	//   Output file to PPM.
	//

	cout << "Writing image to " << outfile << "...";

	os.open(outfile, ios::out);

	os << "P3" << endl;
	os << "# " << outfile << ", output of raytrace" << endl;
	os << width << " " << height << endl;

	os << 255 << endl;

	for(int y = 0; y < height; y++)
	{
		for(int x = 0; x < width; x++)
		{
			os << (int)image_buffer_red  [y*width + x] << " ";
			os << (int)image_buffer_green[y*width + x] << " ";
			os << (int)image_buffer_blue [y*width + x] << " ";
		}

		os << endl;
	}

	os.close();

	delete [] image_buffer_red;
	delete [] image_buffer_green;
	delete [] image_buffer_blue;

	cout << " done!" << endl;

	return 0;
}

//
//   Find the color of a given ray based on intersections with scene objects.
//

SmVector3 getRayColor(Ray &r, int recursion_depth)
{
	float dist_intersect = MAX_DIST;
	SmVector3 alphabetagamma = 0.0f;

	if (recursion_depth < 0) return alphabetagamma; // stop recursion

	Primitive *p = NULL;
	Primitive **pp = &p; // workaround, because pointers always get passed by value

	dist_intersect = getRayNearestIntersection(r, pp, alphabetagamma);

	//
	//   Handle intersections. If none, add no color - else perform further computations.
	//   Note that the barycentric argument alphabetagamma is ignored by spheres.
	//

	SmVector3 ray_color = 0.0f;

	if (p == NULL) return ray_color;
	else
	{
		Object &o = p->owner();

		SmVector3 intersection = r.t(dist_intersect);
		SmVector3 normal = p->normal(intersection, alphabetagamma);
		o.transformNormal(normal); normalize(normal);

		float dot_reflect = dot(-r.dir, normal);
		SmVector3 reflection = (r.dir) + (2*dot_reflect)*normal;
		normalize(reflection);

		//
		//   Recurse to get the color of the reflected ray.
		//

		if (!mostlyEqual(o.krefl, 0.0f))
		{
			Ray reflect_ray(intersection, reflection);

			float f = getRayBoundedIntersection(reflect_ray, MAX_DIST, o.id);

			if (f != -1.0f) // there is something to reflect off of
			{
				ray_color += o.krefl * getRayColor(reflect_ray, recursion_depth - 1);
			}
		}

		if (mostlyEqual(o.krefl, 1.0f)) return ray_color; // perfect mirrors will ONLY reflect

		//
		//   Calculating lighting according to the Phong illumination model.
		//

		SmVector3 phong_color = 0.0f;

		for(int i = 0; i < lights.size(); i++)
		{
			Light &l = lights[i];

			//
			//   Ambient term, includes the color of the object against ambient light.
			//

			phong_color += o.ka * (o.color * l.ambient);

			SmVector3 shadow;
			if (l.type == Light::POINT)            shadow = l.pos - intersection;
			else if (l.type == Light::DIRECTIONAL) shadow = -l.dir;

			float dot_light = dot(shadow, normal);

			if (dot_light > 0.0f) // primitive must face the light
			{
				float dist_light = mag(shadow), attenuate = 1.0f / dist_light;
				normalize(shadow); dot_light /= dist_light; // since we didn't normalize before dotting

				Ray shadow_ray(intersection, shadow);

				float f;
				if (l.type == Light::POINT)            f = getRayBoundedIntersection(shadow_ray, dist_light, o.id);
				else if (l.type == Light::DIRECTIONAL) f = getRayBoundedIntersection(shadow_ray, MAX_DIST, o.id);

				if (f == -1.0f) // nothing between the primitive and the light
				{
					//
					//   Diffuse term, includes light dot product (already checked > 0).
					//

					phong_color += o.kd * (o.color * l.diffuse) * dot_light;

					float dot_spec = dot(shadow, reflection);

					if (dot_spec > 0.0f) // no such thing as negative specularity
					{
						//
						//   Specular term, white glare which includes the roughness exponent.
						//

						phong_color += o.ks * l.specular * pow(dot_spec, PHONG_EXPONENT);
					}
				}
			}
		}

		//
		//   Finally, add in the non-reflected portion of the ray color, appropriately scaled.
		//

		ray_color += (1.0f - o.krefl) * phong_color;

		return ray_color;
	}
}

//
//   Return the distance from the ray origin to the nearest object, also creating a pointer to the primitive
//   that was hit (if a triangle, also pass back barycentric coordinates for alpha, beta, gamma).
//

float getRayIntersection(Ray &r, Primitive **pp, SmVector3 &alphabetagamma, float maxDist, bool cutoff = false, int skip_object = -1)
{
	float f, shortest = maxDist;
	SmVector3 abg = 0.0f;	
	
	for(int i = 0; i < objects.size(); i++)
	{
		bool bbtest = BOUNDING_BOX;

		Object &o = objects[i];
		Ray untransformed_ray = r; o.untransformRay(untransformed_ray);
		normalize(untransformed_ray.dir);

		//
		//   Forward the ray by a very small amount to avoid self-intersections.
		//

		f = EPSILON; untransformed_ray.pos = untransformed_ray.t(EPSILON);

		//
		//   If object has a bounding box, be sure that we intersect it.
		//

		if (bbtest && o.isBounded())
		{
			for(int j = 0; j < o.bounding_box->triangles.size(); j++)
			{
				if (skip_object == o.id) continue;

				Triangle &t = o.bounding_box->triangles[j];
				f = untransformed_ray.intersectTriangle(t, abg);

				if (f >= 0.0f && f < shortest)
				{
					bbtest = false; break;
				}
			}
		}
		else bbtest = false;

		//
		//   Iterate through all faces for intersections (unless we missed the bounding box).
		//

		if (!bbtest) for(int j = 0; j < o.triangles.size(); j++)
		{
			if (skip_object == o.id) continue;
			
			Triangle &t = o.triangles[j];
			f = untransformed_ray.intersectTriangle(t, abg); // also gets barycentric coordinates

			if (f >= 0.0f && f < shortest)
			{
				if (cutoff) return f;
				shortest = f; *pp = &t; alphabetagamma = abg;
			}
		}

		//
		//   Spheres do not have bounding boxes, so we must always test these.
		//

		for(int j = 0; j < o.spheres.size(); j++)
		{
			if (skip_object == o.id) continue;

			Sphere &s = o.spheres[j];
			f = untransformed_ray.intersectSphere(s);

			if (f >= 0.0f && f < shortest)
			{
				if (cutoff) return f;
				shortest = f; *pp = &s;
			}
		}
	}

	if (cutoff) return -1.0f; else return shortest;
}

float getRayNearestIntersection(Ray &r, Primitive **pp, SmVector3 &alphabetagamma)
{
	return getRayIntersection(r, pp, alphabetagamma, MAX_DIST);
}

float getRayBoundedIntersection(Ray &r, float maxDist, int skip_object)
{
	SmVector3 t; return getRayIntersection(r, NULL, t, maxDist, true, skip_object);
}
