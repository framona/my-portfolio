import React, { useState, useEffect } from 'react';
import { Text } from 'react-native';
import { NavigationContainer } from '@react-navigation/native';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { createNativeStackNavigator } from '@react-navigation/native-stack';

import HomeScreen from './src/screens/common/homeScreen';
import RegisterScreen from './src/auth/registerScreen';
import ForgotPassword from './src/auth/forgotPassword';
import LoginScreen from './src/auth/loginScreen';
import ProfileStack from './src/screens/common/profileStack';
import PetStack from './src/screens/user/petStack';
import UserAppointments from './src/screens/user/appointmentsScreen';
import UserMessages from './src/screens/user/messagesScreen';
import VetStack from './src/screens/vet/vetStack';
import VetAppointment from './src/screens/vet/vetAppointments';
import VetMessages from './src/screens/vet/vetMessages';
import AdminStack from './src/screens/admin/adminStack';
import ManageUsers from './src/screens/admin/manageUsers';
import AddVets from './src/screens/admin/addVets';
import Statistics from './src/screens/admin/statistics';

const Tab = createBottomTabNavigator();
const AuthStack = createNativeStackNavigator();

function AuthScreens({ onLogin }) {
  return (
    <AuthStack.Navigator screenOptions={{ headerShown: false }}>
      <AuthStack.Screen name="Login">
        {(props) => <LoginScreen {...props} onLogin={onLogin} />}
      </AuthStack.Screen>
      <AuthStack.Screen name="ForgotPassword" component={ForgotPassword} />
      <AuthStack.Screen name="Register" component={RegisterScreen} />
    </AuthStack.Navigator>
  );
}

export default function App() {
  const [user, setUser] = useState(null);
  const [isReady, setIsReady] = useState(false);

  useEffect(() => {
    const checkLoginStatus = async () => {
      try {
        const savedUser = await AsyncStorage.getItem('user_data');
        if (savedUser) {
          setUser(JSON.parse(savedUser));
        }
      } catch (e) {
        console.error(e);
      } finally {
        setIsReady(true);
      }
    };
    checkLoginStatus();
  }, []);

  const handleLogin = async (data) => {
    try {
      if (data.success && data.user) {
        await AsyncStorage.setItem('user_id', String(data.user.id));
        await AsyncStorage.setItem('user_token', data.token);
        await AsyncStorage.setItem('user_data', JSON.stringify(data.user));
        setUser(data.user);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const handleLogout = async () => {
    try {
      const keys = ['user_id', 'user_token', 'user_data'];
      await AsyncStorage.multiRemove(keys);
      setUser(null);
    } catch (e) {
      console.error(e);
    }
  };

  const getEmoji = (routeName) => {
    switch (routeName) {
      case 'Home': return '🏠';
      case 'Auth': return '🔑';
      case 'Dashboard': return '👤';
      case 'MyPets': return '🐶';
      case 'Appointments': return '📅';
      case 'Messages': return '💬';
      case 'AdminPanel': return '⚙️';
      case 'ManageUsers': return '👥';
      case 'Add New Vet': return '➕';
      case 'Statistics': return '📊';
      default: return '❓';
    }
  };

  if (!isReady) return null;

  return (
    <NavigationContainer>
      <Tab.Navigator
        screenOptions={({ route }) => ({
          headerShown: false,
          tabBarActiveTintColor: '#4CAF50',
          tabBarInactiveTintColor: 'gray',
          tabBarIcon: ({ color, size }) => (
            <Text style={{ fontSize: size, color }}>{getEmoji(route.name)}</Text>
          ),
        })}
      >
        {!user ? (
          <>
            <Tab.Screen name="Home" component={HomeScreen} />
            <Tab.Screen name="Auth">
              {(props) => <AuthScreens {...props} onLogin={handleLogin} />}
            </Tab.Screen>
          </>
        ) : (
          <>
            <Tab.Screen name="Home" component={HomeScreen} />
            {user.role === 'user' && (
              <>
                <Tab.Screen name="Dashboard">
                  {(props) => <ProfileStack {...props} onLogout={handleLogout} />}
                </Tab.Screen>
                <Tab.Screen name="MyPets" component={PetStack} />
                <Tab.Screen name="Appointments" component={UserAppointments} />
                <Tab.Screen name="Messages" component={UserMessages} />
              </>
            )}
            {user.role === 'vet' && (
              <>
                <Tab.Screen name="Dashboard">
                  {(props) => <VetStack {...props} onLogout={handleLogout} />}
                </Tab.Screen>
                <Tab.Screen name="Appointments" component={VetAppointment} />
                <Tab.Screen name="Messages" component={VetMessages} />
              </>
            )}
            {user.role === 'admin' && (
              <>
                <Tab.Screen name="AdminPanel">
                  {(props) => <AdminStack {...props} onLogout={handleLogout} />}
                </Tab.Screen>
                <Tab.Screen name="Add New Vet" component={AddVets} />
                <Tab.Screen name="ManageUsers" component={ManageUsers} />
                <Tab.Screen name="Statistics" component={Statistics} />
              </>
            )}
          </>
        )}
      </Tab.Navigator>
    </NavigationContainer>
  );
}